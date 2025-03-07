<?php

namespace spec\GrumPHP\Process;

use GrumPHP\Collection\ProcessArgumentsCollection;
use GrumPHP\Process\ProcessFactory;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Process\Process;

class ProcessFactorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ProcessFactory::class);
    }

    public function it_should_create_a_process_from_an_arguments_collection()
    {
        $arguments = new ProcessArgumentsCollection(['/usr/bin/grumphp']);

        $process = self::fromArguments($arguments);
        $process->shouldHaveType(Process::class);
        $process->getCommandLine()->shouldBeQuoted('/usr/bin/grumphp');
    }

    public function it_can_create_unquoted_from_string_arguments(): void
    {
        $process = self::fromScalar('/usr/bin/grumphp');
        $process->shouldHaveType(Process::class);
        $process->getCommandLine()->shouldBe('/usr/bin/grumphp');
    }

    public function it_can_create_from_array_arguments(): void
    {
        $process = self::fromScalar(['/usr/bin/grumphp']);
        $process->shouldHaveType(Process::class);
        $process->getCommandLine()->shouldBeQuoted('/usr/bin/grumphp');
    }

    public function getMatchers(): array
    {
        return [
            'beQuoted' => function ($subject, $string) {
                if ('\\' === DIRECTORY_SEPARATOR) {
                    if ($subject !== $string) {
                        throw new FailureException(sprintf(
                            'Expected %s, got %s.',
                            $string,
                            $subject
                        ));
                    }
                    return true;
                }

                $regex = sprintf('{^([\'"])%s\1$}', preg_quote($string));
                if (!preg_match($regex, $subject)) {
                    throw new FailureException(sprintf(
                        'Expected a quoted version of %s, got %s.',
                        $string,
                        $subject
                    ));
                }

                return true;
            }
        ];
    }
}
