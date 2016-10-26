<?php
require_once __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

// php complains if timezone is not set
date_default_timezone_set("UTC");

// create directories if they don't exist
if(!file_exists(__DIR__.'/auth')) {
  mkdir(__DIR__.'/auth', 0700, true);
}
if(!file_exists(__DIR__.'/output')) {
  mkdir(__DIR__.'/output', 0700, true);
}

$message = new ParseMessage(['subjects'=>['Netflix', 'Home Depot', '1800flowers']]);

// start part 1
echo "--------- Part 1 ---------\n";

$subjects = $message->DownloadGmailMessages();

if(!empty($subjects)){
  foreach($message->GetSubjects() as $subject => $count){
    //print the message count for each subject
    echo "$subject: $count\n";
  }

  echo "\nMessages saved to output/\n";
} else {
  echo "\nNo messages found \n";
}

echo "\n--------- Part 2 ---------\n";

$messages = $message->ParseGzipMessages(__DIR__ . '/data/sampleEmails.tar.gz');

if(!empty($messages)){
  // print results of messages
  echo "Parsed ".count($messages)." messages\n";

  // output to a pipe delimted file
  if(!empty($messages)){
    // print headers
    file_put_contents(__DIR__.'/output/part2.csv',"Date|From|Subject\n");
    // print each message headers
    foreach($messages as $message){
      file_put_contents(__DIR__.'/output/part2.csv',implode('|',$message)."\n",FILE_APPEND);
    }
    echo "\nMessage fields saved to output/part2.csv\n";
  }
} else {
  echo "\nNo messages found \n";
}

?>
