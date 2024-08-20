<?php
// Path to the Clover XML file
$coverageFile = './test-reports/cov.xml';

// Load the file contents
$xmlContent = file_get_contents($coverageFile);

// Replace "/var/www/html" with "."
$updatedContent = str_replace('/var/www/html', '.', $xmlContent);

// Save the updated content back to the file
file_put_contents($coverageFile, $updatedContent);

echo "Clover report updated successfully!\n";