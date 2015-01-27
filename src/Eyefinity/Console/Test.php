<?php

namespace Eyefinity\Console;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Eyefinity\Validator;

class Test extends Command {

    protected function configure() {
        $this
            ->setName('test')
            ->setDescription('Test the full set of PDF file to validate converter')
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'If set, the task will run in debug mode'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $app = $this->getSilexApplication();
        $output->writeln("[Test full set of files] ");
        $output->writeln("Listing files... ");

        $dir = __DIR__.'/../../../test';
        chdir($dir);
        $samples = dir($dir.'/samples');
        $count = 0;
        while (false !== ($entry = $samples->read())) {
            if (substr($entry, 0, 1) == '.')
                continue;

            $count++;
            $output->write("[Trying samples/".$entry."] ");

            // original format
            $validator = new Validator($app, 'samples/'.$entry);
            //$validator->validatePDFA1();

            // clean
            @unlink($dir.'/temp.pdf');
            //@unlink($dir.'/temp_base64.txt');

            // convert
            //$r = shell_exec('curl --form "source=@samples/d926.pdf" --form key=cledetestsecurite --form store_id=445 --output temp_base64.txt http://localhost:8080/convert');
            $ascii = shell_exec('curl --form "source=@samples/d926.pdf" --form key=cledetestsecurite --form store_id=445 http://localhost:8080/convert');
            file_put_contents('temp.pdf', base64_decode($ascii));

            // test
            // @todo

            $output->writeln(''); // \n
            if ($count >= 5)
                break;
        }
        $output->writeln($count." found");
    }
}