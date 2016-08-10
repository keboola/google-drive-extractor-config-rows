<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 10/08/16
 * Time: 16:45
 */

namespace Keboola\GoogleDriveExtractor\Test;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class FunctionalTest extends BaseTest
{
    public function testRun()
    {
        $process = $this->runProcess();
        $this->assertEquals(0, $process->getExitCode());
    }

    /**
     * @return Process
     */
    private function runProcess()
    {
        $dataPath = '/tmp/data-test';
        $fs = new Filesystem();
        $fs->remove($dataPath);
        $fs->mkdir($dataPath);
        $fs->mkdir($dataPath . '/out/tables');

        $yaml = new Yaml();
        file_put_contents($dataPath . '/config.yml', $yaml->dump($this->config));

        $process = new Process(sprintf('php run.php --data=%s', $dataPath));
        $process->run();

        return $process;
    }
}
