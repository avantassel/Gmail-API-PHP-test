<?php
define('APPLICATION_NAME', 'Return Path Test');
define('CREDENTIALS_PATH', __DIR__ . '/../auth/gmail-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/../auth/client_secret.json');
define('API_KEY', 'AIzaSyAWaSiBlb3CYBuwggtAfDogq2VmQN0UymA');

define('SCOPES', implode(' ', array(
  Google_Service_Gmail::GMAIL_READONLY)
));

class ParseMessage {

  protected $subjects   = [];

  protected $gzmessages = [];

  private $gmail_service = null;

  public function __construct($args=[]){
    if(isset($args['subjects']) && is_array($args['subjects']))
      $this->subjects = $args['subjects'];

    // init gmail client
    $client = self::getGmailClient();
    $this->gmail_service = new Google_Service_Gmail($client);
  }

  // this is copied from google php docs
  // https://developers.google.com/drive/v3/web/quickstart/php
  private function getGmailClient() {

    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */

    $client = new Google_Client();
    // $client->setDeveloperKey(API_KEY);
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAuthConfig(CLIENT_SECRET_PATH);
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = self::expandHomeDirectory(CREDENTIALS_PATH);
    if (file_exists($credentialsPath)) {
      $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
      // Request authorization from the user.
      $authUrl = $client->createAuthUrl();
      printf("Open the following link in your browser:\n%s\n", $authUrl);
      print 'Enter verification code: ';
      $authCode = trim(fgets(STDIN));

      // Exchange authorization code for an access token.
      $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

      // Store the credentials to disk.
      if(!file_exists(dirname($credentialsPath))) {
        mkdir(dirname($credentialsPath), 0700, true);
      }
      file_put_contents($credentialsPath, json_encode($accessToken));
      printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->refreshToken($accessToken);
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
      $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
      file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
  }

  private function expandHomeDirectory($path) {
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
      $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
    }
    return str_replace('~', realpath($homeDirectory), $path);
  }

  public function GetSubjects(){
    return $this->subjects;
  }

  public function GetGzipMessages(){
    return $this->gzmessages;
  }

  public function DownloadGmailMessages(){
    $subjects = [];
    try {
      // loop through subjects
      foreach($this->subjects as $subject){
        // set subject query param
        $messages = $this->gmail_service->users_messages->listUsersMessages('me',['labelIds' => 'INBOX', 'q' => "subject:\"$subject\""]);
        // get all messages with subject
        $list = $messages->getMessages();
        $subjects[$subject] = count($list);
        // download each message
        foreach($list as $m){
          $message = $this->gmail_service->users_messages->get('me', $m->id, ['format'=>'raw']);
          $replacedRawMessage = strtr($message->raw,'-_', '+/');
          $decodedRawMessage = base64_decode($replacedRawMessage);
          // write message to a file
          file_put_contents(__DIR__.'/../output/'.$m->id.'.msg',$decodedRawMessage);
        }
      }
    } catch(Exception $ex){
      // catch any errors, most likely an auth error
      echo $ex->getMessage();
      return false;
    }
    $this->subjects = $subjects;
    return $subjects;
  }

  public function ParseGzipMessages($gzip_file){
    // extract files from tar ball
    $lines = gzfile($gzip_file);
    // init messages and msg arrays
    $messages = [];
    $msg = [];

    // loop through lines in each message
    foreach($lines as $line){
        // reset message on new header
        if($line == "\n"){
          if(count($msg)){
            // sort the message array
            ksort($msg);
            // add message to messages array
            if(count($msg)===3){
              $messages[]=$msg;
            }
          }
          $msg = [];// clear message array
        }
        // get Date header
        if(strpos($line,'Date:')===0){
          $msg['date'] = trim(substr($line,strpos($line,'Date:')+5));
        }
        // get From header
        if(strpos($line,'From:')===0){
          $msg['from'] = trim(substr($line,strpos($line,'From:')+5));
        }
        // get Subject header
        if(strpos($line,'Subject:')===0){
          $msg['subject'] = trim(substr($line,strpos($line,'Subject:')+8));
        }
    }
    $this->gzmessages = $messages;
    return $messages;
  }
}
?>
