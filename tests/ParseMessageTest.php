<?php
use PHPUnit\Framework\TestCase;

class ParseMessageTest extends TestCase
{
    protected $message  = null;

    public function __construct(){
      $this->message = new ParseMessage(['subjects'=>['Netflix', 'Home Depot', '1800flowers']]);
    }

    public function testSubjectCount()
    {
        // Get Subjects
        $subjects = $this->message->GetSubjects();

        // Assert
        $this->assertEquals(3, count($subjects));
    }

    public function testMessageCount()
    {
        $messages = $this->message->ParseGzipMessages(__DIR__ . '/../data/sampleEmails.tar.gz');

        // Assert
        $this->assertEquals(12, count($messages));
    }

    // ...
}
