<?php
// GitHub Webhook Handler for seait.edu.ph

$repo_dir = "/home/pms.seait.edu.ph/public_html/tms_api";
$branch = "main";

// Run git pull with full path to git
exec("cd {$repo_dir} && /usr/bin/git fetch --all 2>&1 && /usr/bin/git reset --hard origin/{$branch} 2>&1", $output, $return_var);

// Response
header("Content-Type: text/plain");
echo "Git Pull Result:\n";
echo implode("\n", $output);
echo "\nExit Code: $return_var\n";
?>
