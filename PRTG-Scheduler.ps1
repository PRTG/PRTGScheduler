#requires -version 4.0
# ___ ___ _____ ___
#| _ \ _ \_   _/ __|
#|  _/   / | || (_ |
#|_| |_|_\ |_| \___|
# Scheduler
# ================================
# This is the Sensor for the PRTG scheduler which will execute the scheduler application
# that applies maintenance windows to PRTG objects, according to their configuration. 
# The output is then displayed as JSON so that PRTG can interpret it. 
#
# Version History
# ----------------------------
# 06/2017    1.0      initial release
# # # # # # # # # # # # # # # # # # # # # # # # # #

#region
#  ___ ___  _  _ ___ ___ ___ 
# / __/ _ \| \| | __|_ _/ __|
#| (__|(_) | .` | _| | | (_ |
# \___\___/|_|\_|_| |___\___|
# # # # # # # # # # # # # # #
$progressPreference  = 'silentlyContinue'
$phpPath = "C:\PHP\"

$PrtgError = @"
<?xml version='1.0' encoding='UTF-8' ?><prtg>
<error>1</error><text>{0}</text>
</prtg>
"@

$jsMenuEntry = '' 
#endregion

#region 
# ___ _   _ _  _  ___ _____ ___ ___  _  _ ___ 
#| __| | | | \| |/ __|_   _|_ _/ _ \| \| / __|
#| _|| |_| | .` | (__  | |  | | (_) | .` \__ \
#|_|  \___/|_|\_|\___| |_| |___\___/|_|\_|___/
# # # # # # # # # # # # # # # # # # # # # # #
#
# Basic
# # # # 
Function This-ShowMessage([string]$type,$message){

        Write-Host ("[{0}] " -f (Get-Date)) -NoNewline; 

        switch ($type){
            "success"       { Write-Host "    success    "  -BackgroundColor Green      -ForegroundColor White -NoNewline; }
            "information"   { Write-Host "  information  "  -BackgroundColor DarkCyan   -ForegroundColor White -NoNewline; }
            "warning"       { Write-Host "    warning    "  -BackgroundColor DarkYellow -ForegroundColor White -NoNewline; }
            "error"         { Write-Host "     error     "  -BackgroundColor DarkRed    -ForegroundColor White -NoNewline; }
            default         { Write-Host "     notes     "  -BackgroundColor DarkGray   -ForegroundColor White -NoNewline; }
        }
        
        Write-Host (" {0}{1}" -f $message,$Global:blank) 
}

Function This-TestPHP(){
    if(Test-Path -Path "$($phpPath)php.exe")
    { return $true;  }
    else 
    { return $false; }
}

Function This-AddMenuEntry(){
    $customJS = (Get-Content -Path "C:\Program Files (x86)\PRTG Network Monitor\webroot\javascript\scripts_custom.js")

}

Function This-ExecutePHP($command = ""){
   
    $pinfo = New-Object System.Diagnostics.ProcessStartInfo
    $pinfo.FileName = "$($phpPath)php.exe"
    $pinfo.RedirectStandardError = $true
    $pinfo.RedirectStandardOutput = $true
    $pinfo.UseShellExecute = $false
    $pinfo.Arguments = "$($phpPath)app\index.php PRTGScheduler $($command)"
    $p = New-Object System.Diagnostics.Process
    $p.StartInfo = $pinfo
    $p.Start() | Out-Null
    $p.WaitForExit()
    return $p.StandardOutput.ReadToEnd();
  
}

#endregion

if($ApiToken.Length -eq 0)
{  Write-Host ([string]::Format($PrtgError,"PHP.exe not found under $($phpPath). Please make sure it's installed!")); }

if(This-TestPHP)
{ This-ExecutePHP -command "setMaintenance $($ApiToken)" }
else
{ Write-Host ([string]::Format($PrtgError,"PHP.exe not found under $($phpPath). Please make sure it's installed!")); }