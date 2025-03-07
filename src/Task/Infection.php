<?php

declare(strict_types=1);

namespace GrumPHP\Task;

use GrumPHP\Formatter\ProcessFormatterInterface;
use GrumPHP\Runner\TaskResult;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\Config\ConfigOptionsResolver;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractExternalTask<ProcessFormatterInterface>
 */
class Infection extends AbstractExternalTask
{
    public static function getConfigurableOptions(): ConfigOptionsResolver
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'threads' => null,
            'test_framework' => null,
            'only_covered' => false,
            'show_mutations' => false,
            'verbose' => false,
            'configuration' => null,
            'min_msi' => null,
            'min_covered_msi' => null,
            'mutators' => [],
            'ignore_patterns' => [],
            'triggered_by' => ['php'],
            'skip_initial_tests' => false,
            'coverage' => null,
        ]);

        $resolver->addAllowedTypes('threads', ['null', 'int']);
        $resolver->addAllowedTypes('test_framework', ['null', 'string']);
        $resolver->addAllowedTypes('only_covered', ['bool']);
        $resolver->addAllowedTypes('show_mutations', ['bool']);
        $resolver->addAllowedTypes('verbose', ['bool']);
        $resolver->addAllowedTypes('configuration', ['null', 'string']);
        $resolver->addAllowedTypes('min_msi', ['null', 'integer']);
        $resolver->addAllowedTypes('min_covered_msi', ['null', 'integer']);
        $resolver->addAllowedTypes('mutators', ['array']);
        $resolver->addAllowedTypes('ignore_patterns', ['array']);
        $resolver->addAllowedTypes('triggered_by', ['array']);
        $resolver->addAllowedTypes('skip_initial_tests', ['bool']);
        $resolver->addAllowedTypes('coverage', ['null', 'string']);

        return ConfigOptionsResolver::fromOptionsResolver($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function canRunInContext(ContextInterface $context): bool
    {
        return $context instanceof GitPreCommitContext || $context instanceof RunContext;
    }

    /**
     * {@inheritdoc}
     */
    public function run(ContextInterface $context): TaskResultInterface
    {
        $config = $this->getConfig()->getOptions();
        $files = $context->getFiles()->extensions($config['triggered_by']);

        $files = $files->notPaths($config['ignore_patterns']);

        if (0 === \count($files)) {
            return TaskResult::createSkipped($this, $context);
        }

        $arguments = $this->processBuilder->createArgumentsForCommand('infection');
        $arguments->add('--no-interaction');
        $arguments->add('--ignore-msi-with-no-mutations');
        $arguments->addOptionalArgument('--threads=%s', $config['threads']);
        $arguments->addOptionalArgument('--test-framework=%s', $config['test_framework']);
        $arguments->addOptionalArgument('--only-covered', $config['only_covered']);
        $arguments->addOptionalArgument('--show-mutations', $config['show_mutations']);
        $arguments->addOptionalArgument('-v', $config['verbose']);
        $arguments->addOptionalArgument('--configuration=%s', $config['configuration']);
        $arguments->addOptionalArgument('--min-msi=%s', $config['min_msi']);
        $arguments->addOptionalArgument('--min-covered-msi=%s', $config['min_covered_msi']);
        $arguments->addOptionalArgument('--coverage=%s', $config['coverage']);
        $arguments->addOptionalArgument('--skip-initial-tests', $config['skip_initial_tests']);
        $arguments->addOptionalCommaSeparatedArgument('--mutators=%s', $config['mutators']);

        if ($context instanceof GitPreCommitContext) {
            $arguments->addArgumentWithCommaSeparatedFiles('--filter=%s', $files);
        }

        $process = $this->processBuilder->buildProcess($arguments);
        $process->run();

        if (!$process->isSuccessful()) {
            return TaskResult::createFailed($this, $context, $this->formatter->format($process));
        }

        return TaskResult::createPassed($this, $context);
    }
}
