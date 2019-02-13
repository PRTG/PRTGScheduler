require "logger"
require "ice_cube"
require 'yaml'
require 'json'
require 'httparty'
require 'awesome_print'
require 'active_support'
require 'active_support/core_ext'
require 'i18n'
require 'tzinfo'
require 'rspec'
require 'date'

# # LOAD CONFIGURATION
# Load the actual configuration
$configuration  = YAML.load_file('scheduler.configuration.yaml')
# Configure the language according to the configuration
I18n.config.available_locales = $configuration["scheduler"]["available_locales"]
I18n.locale = $configuration["scheduler"]["language"]


# # CONFIGURATION
# required for PRTG requests
$headers = { "Accept-Encoding" => "*" }

# Connection String base configuration - all other URLs will have the dictionary values added within the corresponding function(s)
$prtgConnection = { 
    prtgProtocol: $configuration["prtg"]["protocol"], 
    prtgHost:     $configuration["prtg"]["host"], 
    prtgPort:     $configuration["prtg"]["port"],
    prtgUsername: $configuration["prtg"]["username"],
    prtgPasshash: $configuration["prtg"]["passhash"],
}

# This will log to stdout and the console as well. 
class Logging

    def initialize(*targets)
        @targets = targets
        end
    
        def write(*args)
        @targets.each {|t| t.write(*args)}
        end
    
        def close
        @targets.each(&:close)
        end
    end

    log_file = File.open("prtg.scheduler.log", "a")
    $log = Logger.new Logging.new(STDOUT, log_file)

    $log.level = Logger::INFO
    # configure the logging format 
    $log.formatter = proc do |severity, datetime, progname, msg|
        "#{datetime} [#{severity}] #{msg}\n"

end
# # 

# Handle missing gem requirements
module Setup
    module_function 

    # This will install any missing gems defined as requirements. 
    def installing_missing_gems(&block)
        yield
      rescue LoadError => e
        gem_name = e.message.split('--').last.strip
        install_command = 'gem install ' + gem_name
        
        # install missing gem
        puts 'Probably missing gem: ' + gem_name
        print 'Auto-install it? [yN] '
        gets.strip =~ /y/i or exit(1)
        system(install_command) or exit(1)
        
        # retry
        Gem.clear_paths
        puts 'Trying again ...'
        require gem_name
        retry
      end
end

# this contains all methods to interact with PRTG. Setting maintenances, reading objects, etc. 
module PRTG
    module_function

    # output the message to PRTG in case there's an error
    def show_error_message(type, message)
        return (" { 'prtg': { '#{type}': '1', 'text': '#{message}' } }")
    end

    # lets get all defined objects first (i.e. objects that contain the word maintenance in their comments)
    def get_objects
        
        objects = Array.new

        # create the corresponding URLs for every given object type
        ["groups", "devices","sensors"].each {|object_type| 
            $prtgConnection[:objectType] = object_type 
            address =  $configuration["prtg"]["api"]["get_objects"] % $prtgConnection
        }
    end 

    # this will ensure connectivity to the PRTG - if we can't connect, we won't proceed.
    def check_connectivity
        begin
            # the actual response query
            response = HTTParty.get($configuration["prtg"]["api"]["check_login"] % $prtgConnection, :headers => $headers)

            case response.code
                when 200
                    $log.info("Connection to PRTG Server successful.")
                when 401
                    $log.error("Connection to PRTG Server failed. Got 'Access denied'. Please check the credentials in the configuration.")
                when 404
                    $log.error("Connection to PRTG Server failed. Got 'Not found'. Please check the connection details of your PRTG Server")
                else
                    log.error("Connection to PRTG Server failed. Got a #{response.code} error.")
            end
        # capture exceptions and output the corresponding PRTG error
        rescue => exception
            $log.error("Connection to PRTG Server failed. Got the following exception: #{exception.message}")
            abort(show_error_message("error", "Could not connect to the PRTG Server. Check the log for details."))
        end
    end

    # this is used to update the corresponding object with the given maintenance window
    def update_object
    end

end


# This will verify the maintenance lines, create maintenances, etc.
module Maintenance
    module_function

    # the actual maintenance window object that will be used for setting maintenances and creating schedule objects
    class MaintenanceWindow        
       attr_accessor :objid, :active, :rule

        def objid(objid)
            @objid = objid
        end

        def active(active = true)
            @active = active
        end 

        def rule(rule)
            @rule = rule
        end
    end
    

    # verify the read maintenance window - ensure that the syntax is properly configured and set the type accordingly. 
    def verify_definition()

        # we need to extract the actual maintenance first and do some checkings
        extract_maintenance = /maintenance\[(.*?)\]/    # extract the maintenance definition itself
             check_timezone = /([a-zA-Z]+\/[a-zA-Z]+)/  # check if there's a IANA timezone defined (e.g. Europe/Berlin)
           check_recurrence = /(monthly|weekly|daily)/  # check if the definition is recurring
             extract_string = /\D+[a-z]/                # extract the string of 1mo,2fr, etc.
             extract_digit  = /^-?\d+/                  # extract the numeric value of 1mo, 2fr, etc.

        # maintenance testing 
        #maintenance_definition = "maintenance[20/06/2017|20:00|22:00|Maintenance Window]" # one-time
        #maintenance_definition = "maintenance[20/06/2017|20:00|04:00|Maintenance Window]" # overlapping
        maintenance_definition = "maintenance[#monthly-1-20/06/2017|1mo,2tu,-1fr,1su|all|all|20:00|22:00|New Window]" # recurring

        # let's create a new hash for the maintenance 
        maintenance = Hash.new 

        definition = maintenance_definition.slice(extract_maintenance,1).split("|")

        # check if the maintenance is disabled 
        if definition[0] =~ /#/
            maintenance["active"] = false # if the string starts with a #, it's disabled.<
            definition[0].sub! '#',''
        end 

        # lets check if the window is a recurring one first. This is done by checking the definition contains either monthly, weekly or daily
        if definition[0].split("-")[0] =~ check_recurrence
            maintenance_options = definition[0].split("-")
            maintenance["recurrence"]   = maintenance_options[0] # daily, weekly, monthly 
            maintenance["recurr_every"] = maintenance_options[1] # start at the nth day, week or month
            maintenance["start_date"]   = maintenance_options[2] # the start date. previous dates will be ignored

            # The maintenance is recurring, let's check the weekdays
            case definition[1]
                when /all/                                       # all days of the week
                    maintenance["weekdays"] = "all" 
                when /,/                                         # multiple, but not all week days. let's put them into arrays for easier management
                    maintenance["weekdays"] = Hash.new           # lets create a new hash for the weekdays
                    definition[1].split(",").each do |weekday|  
                        # for each defined weekday, create a new hashtable entry
                        # lets curate it for ice_cube, as it requires an array of weekdays using symbols
                        # since there are possible multiple occurrences for one weekday, let's add them instead of overwriting it. 
                        maintenance["weekdays"][weekday.scan(extract_string).first.to_sym] = weekday.scan().first.map(&:to_i)
                    end      
                else          # this would match only one weekday is given
                    maintenance["weekdays"] = definition[1]
            end
        #maintenance = 

     ap maintenance
        end
        
        # maintenance[0] =~ disabled
        #    active = false
        #end 

        # First, let's check what's the recurrence (monthly, weekly, daily), how often it occurs and when its starting
end
end
$log.info("Checking installed gems.")
=begin
Setup.installing_missing_gems do
    require "logger"
    require "ice_cube"
    require 'yaml'
    require 'json'
    require 'httparty'
    require 'awesome_print'
    require 'active_support'
    require 'active_support/core_ext'
    require 'i18n'
    require 'tzinfo'
    require 'rspec'
    require 'date'
    # some code using those gems
  end
=end 
$log.info("Done.")



#PRTG.GetObjects
#PRTG.check_connectivity

#schedule = IceCube::Schedule.new(now = Time.now) do |s|
#    s.add_recurrence_rule(IceCube::Rule.daily.count(4))
#    s.add_exception_time(now + 1.day)
#  end
now = Time.now.in_time_zone("Asia/Tokyo")

schedule = IceCube::Schedule.new(now, :duration => 3600)

#schedule = IceCube::Schedule.new()
#schedule.add_recurrence_rule IceCube::Rule.day_of_week(:monday,:friday)

#schedule.note = "Tests"
#puts(schedule.to_yaml)
#ap schedule,options={}

#rule = IceCube::Rule.monthly(2).day_of_week(:tuesday => [1, -1], :wednesday => [2], :sunday => [1,3])
#puts(rule.to_s.encode('utf-8'))
#MaintenanceWindow = Window.new
#MaintenanceWindow.active = true



#ap MaintenanceWindow
#Maintenance.verify_definition
schedule.add_recurrence_rule IceCube::Rule.weekly.day_of_week(:tuesday => [1, -1],:wednesday => [1, -1])

puts(schedule.to_s)