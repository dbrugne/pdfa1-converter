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
            $output->write("\t before:".(($validator->validatePDFA1())?'OK':'NOK'));

            // clean
            @unlink($dir.'/temp.pdf');

            // convert
            $ascii = shell_exec('curl --silent --form "source=@samples/d926.pdf" --form key=cledetestsecurite --form store_id=445 http://localhost:8080/convert');
            file_put_contents('temp.pdf', base64_decode($ascii));

            // test
            $validator = new Validator($app, 'temp.pdf');
            $output->write("\t after:".(($validator->validatePDFA1())?'OK':'NOK'));

            $output->writeln(''); // \n
            if ($count >= 1000)
                break;
        }
        $output->writeln($count." found");
    }
}