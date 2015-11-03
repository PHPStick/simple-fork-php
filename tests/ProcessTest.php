<?php

/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/10/8
 * Time: 16:45
 */
class ProcessTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Jenner\SimpleFork\Process
     */
    protected $process_thread;
    /**
     * @var \Jenner\SimpleFork\Process
     */
    protected $process_runable;
    /**
     * @var \Jenner\SimpleFork\Process
     */
    protected $process_callback;

    public function testFailed()
    {
        $process = new \Jenner\SimpleFork\Process(function () {
            function_not_exists();
        });
        $process->start();
        $process->wait();
        $this->assertEquals(255, $process->errno());
        $this->assertEquals("Unknown error 255", $process->errmsg());
    }

    public function testOn(){
        $ipc_file = '/tmp/ipc_test';
        touch($ipc_file);
        $process = new \Jenner\SimpleFork\Process(function(){

        });

        $process->on(\Jenner\SimpleFork\Process::BEFORE_START, function() use ($ipc_file){
            $cache = new \Jenner\SimpleFork\Cache\SharedMemory(33554432, $ipc_file);
            $cache->set('test', 'test');
            sleep(3);
            return true;
        });

        $process->on(\Jenner\SimpleFork\Process::BEFORE_EXIT, function() use ($ipc_file){
            $cache = new \Jenner\SimpleFork\Cache\SharedMemory(33554432, $ipc_file);
            $cache->delete('test');
            sleep(3);
            return true;
        });

        $cache = new \Jenner\SimpleFork\Cache\SharedMemory(33554432, $ipc_file);
        $this->assertFalse($cache->has('test'));
        $process->start();
        sleep(1);
        $this->assertEquals('test', $cache->get('test'));
        sleep(4);
        $process->shutdown();
        sleep(1);
        $this->assertFalse($cache->has('test'));
    }

    public function testShutdown()
    {
        $process = new \Jenner\SimpleFork\Process(function () {
            sleep(3);
        });
        $time = time();
        $process->start();
        $process->shutdown(SIGKILL);
        $this->assertFalse($process->isRunning());
        $this->assertTrue(time() - $time < 3);
        $this->assertTrue($process->ifSignal());
        $this->assertEquals(0, $process->errno());
    }


    public function testWait()
    {
        $this->process_thread = new MyThread();
        $this->process_runable = new \Jenner\SimpleFork\Process(new MyRunnable());
        $this->process_callback = new \Jenner\SimpleFork\Process(function () {
            for ($i = 0; $i < 3; $i++) {
//                echo "callback pid:" . getmypid() . PHP_EOL;
            }
        });
        $this->process_thread->start();
        $this->process_thread->wait();
        $this->assertEquals(0, $this->process_thread->errno());
        $this->assertEquals($this->process_thread->errno(), 0);
        $this->assertEquals($this->process_thread->errmsg(), 'Success');
        $this->assertEquals($this->process_thread->isRunning(), false);

        $this->process_runable->start();
        $this->process_runable->wait();
        $this->assertEquals(0, $this->process_runable->errno());

        $this->process_callback->start();
        $this->process_callback->wait();
        $this->assertEquals(0, $this->process_callback->errno());
    }


}

class MyThread extends \Jenner\SimpleFork\Process
{
    public function run()
    {
        for ($i = 0; $i < 3; $i++) {
//            echo "thread pid:" . getmypid() . PHP_EOL;
        }
    }
}

class MyRunnable implements \Jenner\SimpleFork\Runnable
{

    /**
     * process entry
     * @return mixed
     */
    public function run()
    {
        for ($i = 0; $i < 3; $i++) {
//            echo "runnable pid:" . getmypid() . PHP_EOL;
        }
    }
}