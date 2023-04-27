<?php

namespace Tests\Unit\Services;

use App\Constants\Constants;
use App\Services\LoggingService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LoggingServiceTest extends TestCase
{
    protected LoggingService $loggingService;

    public function setUp(): void
    {
        parent::setUp();
        $this->loggingService = app()->make(LoggingService::class);
    }

    public function testGetLevelLoggingEmptyFlagsValue()
    {
        Config::set('app.flags', '');
        $result = $this->loggingService->getLevelLogging();
        $this->assertEmpty($result);
    }

    public function testGetLevelLoggingEmptyLogValue()
    {
        Config::set('app.flags', Constants::FLAG_LOG);
        Config::set('logging.log', '');
        $result = $this->loggingService->getLevelLogging();
        $this->assertEquals(collect([]), $result);
    }

    public function testGetLevelLoggingSomeLogValue()
    {
        $collectionLog = collect([Constants::LOG_EXCEPTION, Constants::LOG_INFO]);
        $log = implode(',', $collectionLog->toArray());
        Config::set('app.flags', Constants::FLAG_LOG);
        Config::set('logging.log', $log);
        $result = $this->loggingService->getLevelLogging();
        $this->assertEquals($collectionLog, $result);
    }

    /**
     * @dataProvider testIsLoggingFunctionsDataProvider
     */
    public function testIsLoggingWarningReturnFalse($flags, $log, $method, $expected)
    {
        Config::set('app.flags', $flags);
        Config::set('logging.log', $log);
        $result = $this->loggingService->$method();
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider logFunctionsDataProvider
     */
    public function testExceptionMethodWithContext($message, $context, $method, $logMethod, $constant)
    {
        $expectedMessage = sprintf('%s: %s', $constant, $message);
        Log::expects($logMethod)->with($expectedMessage, $context);
        $this->loggingService->$method($message, $context);
    }

    private function testIsLoggingFunctionsDataProvider(): array {
        return [
            'isLoggingWarning_false' => [
                'flags' => Constants::FLAG_LOG,
                'log' => ' EXCEPTION   , INFO   ',
                'method' => 'isLoggingWarning',
                'expected' => false,
            ],
            'isLoggingWarning_true' => [
                'flags' => Constants::FLAG_LOG,
                'log' => '  EXCEPTION, LOG,  WARNING  ',
                'method' => 'isLoggingWarning',
                'expected' => true,
            ],
            'isLoggingException_false' => [
                'flags' => Constants::FLAG_LOG,
                'log' => '   LOG ,INFO',
                'method' => 'isLoggingException',
                'expected' => false,
            ],
            'isLoggingException_true' => [
                'flags' => Constants::FLAG_LOG,
                'log' => 'EXCEPTION , LOG  ',
                'method' => 'isLoggingException',
                'expected' => true,
            ],
            'isLoggingInfo_false' => [
                'flags' => Constants::FLAG_LOG,
                'log' => 'EXCEPTION  ,WARNING',
                'method' => 'isLoggingInfo',
                'expected' => false,
            ],
            'isLoggingInfo_true' => [
                'flags' => Constants::FLAG_LOG,
                'log' => 'INFO   , LOG ',
                'method' => 'isLoggingInfo',
                'expected' => true,
            ],
            'isLoggingTrace_false' => [
                'flags' => Constants::FLAG_LOG,
                'log' => '  EXCEPTION, INFO',
                'method' => 'isLoggingTrace',
                'expected' => false,
            ],
            'isLoggingTrace_true' => [
                'flags' => Constants::FLAG_LOG,
                'log' => 'EXCEPTION, LOG, TRACE, INFO   ',
                'method' => 'isLoggingTrace',
                'expected' => true,
            ],
        ];
    }

    private function logFunctionsDataProvider(): array {
        return [
            'exception_no_context' => [
                'message' => 'Exception - Some message no context',
                'context' => [],
                'method' => 'exception',
                'logMethod' => 'error',
                'constant' => Constants::LOG_EXCEPTION,
            ],
            'exception_with_context' => [
                'message' => 'Exception - Some message with context',
                'context' => ['code' => 111, 'trace' => ['some', 'trace for exception']],
                'method' => 'exception',
                'logMethod' => 'error',
                'constant' => Constants::LOG_EXCEPTION,
            ],
            'info_no_context' => [
                'message' => 'Info - Some message no context',
                'context' => [],
                'method' => 'info',
                'logMethod' => 'info',
                'constant' => Constants::LOG_INFO,
            ],
            'info_with_context' => [
                'message' => 'Info - Some message with context',
                'context' => ['code' => 222, 'trace' => ['some', 'trace for info']],
                'method' => 'info',
                'logMethod' => 'info',
                'constant' => Constants::LOG_INFO,
            ],
            'warning_no_context' => [
                'message' => 'Warning - Some message no context',
                'context' => [],
                'method' => 'warning',
                'logMethod' => 'warning',
                'constant' => Constants::LOG_WARNING,
            ],
            'warning_with_context' => [
                'message' => 'Warning - Some message with context',
                'context' => ['code' => 333, 'trace' => ['some', 'trace for warning']],
                'method' => 'warning',
                'logMethod' => 'warning',
                'constant' => Constants::LOG_WARNING,
            ],
            'trace_no_context' => [
                'message' => 'Trace - Some message no context',
                'context' => [],
                'method' => 'trace',
                'logMethod' => 'info',
                'constant' => Constants::LOG_TRACE,
            ],
            'trace_with_context' => [
                'message' => 'Trace - Some message with context',
                'context' => ['code' => 144423, 'trace' => ['some', 'trace for trace']],
                'method' => 'trace',
                'logMethod' => 'info',
                'constant' => Constants::LOG_TRACE,
            ],
        ];
    }
}
