<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drupal\AppConsole\Generator;

class Generator
{
    private $skeletonDirs;

    private $module_path;

    private $translator;

    private $files;

    /**
     * Sets an array of directories to look for templates.
     *
     * The directories must be sorted from the most specific to the most
     * directory.
     *
     * @param array $skeletonDirs An array of skeleton dirs
     */
    public function setSkeletonDirs($skeletonDirs)
    {
        $this->skeletonDirs = is_array($skeletonDirs) ? $skeletonDirs : array($skeletonDirs);
    }

    protected function render($template, $parameters)
    {
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem($this->skeletonDirs), array(
          'debug' => true,
          'cache' => false,
          'strict_variables' => true,
          'autoescape' => false,
        ));

        $twig->addFunction($this->getServicesAsParameters());
        $twig->addFunction($this->getServicesAsParametersKeys());
        $twig->addFunction($this->getArgumentsFromRoute());
        $twig->addFunction($this->getServicesClassInitialization());
        $twig->addFunction($this->getServicesClassInjection());
        $twig->addFunction($this->getTagsAsArray());

        return $twig->render($template, $parameters);
    }

    protected function renderFile($template, $target, $parameters, $flag = null)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        if (file_put_contents($target, $this->render($template, $parameters), $flag)) {
            $this->files[] = str_replace(DRUPAL_ROOT . '/', '', $target);

            return true;
        }

        return false;
    }

    protected function renderView($template, $parameters)
    {
        return $this->render($template, $parameters);
    }

    public function getModulePath($module_name)
    {
        if (!$this->module_path) {
            $this->module_path = DRUPAL_ROOT . '/' . drupal_get_path('module', $module_name);
        }

        return $this->module_path;
    }

    public function getControllerPath($module_name)
    {
        return $this->getModulePath($module_name) . '/src/Controller';
    }

    public function getTestPath($module_name, $test_type)
    {
        return $this->getModulePath($module_name) . '/Tests/' . $test_type;
    }

    public function getFormPath($module_name)
    {
        return $this->getModulePath($module_name) . '/src/Form';
    }

    public function getPluginPath($module_name, $plugin_type)
    {
        return $this->getModulePath($module_name) . '/src/Plugin/' . $plugin_type;
    }

    public function getAuthenticationPath($module_name, $authentication_type)
    {
        return $this->getModulePath($module_name) . '/src/Authentication/' . $authentication_type;
    }

    public function getCommandPath($module_name)
    {
        return $this->getModulePath($module_name) . '/src/Command';
    }

    public function getSourcePath($module_name)
    {
        return $this->getModulePath($module_name) . '/src';
    }

    public function getEntityPath($module_name)
    {
        return $this->getModulePath($module_name) . '/src/Entity';
    }

    public function getTemplatePath($module_name)
    {
        return $this->getModulePath($module_name) . '/templates';
    }

    public function getTranslationsPath($module_name)
    {
        return $this->getModulePath($module_name) . '/config/translations';
    }

    public function getServicesAsParameters()
    {
        $servicesAsParameters = new \Twig_SimpleFunction('servicesAsParameters', function ($services) {
            $returnValues = [];
            foreach ($services as $service) {
                $returnValues[] = sprintf('%s $%s', $service['short'], $service['machine_name']);
            }

            return $returnValues;
        });

        return $servicesAsParameters;
    }

    public function getServicesAsParametersKeys()
    {
        $servicesAsParametersKeys = new \Twig_SimpleFunction('servicesAsParametersKeys', function ($services) {
            $returnValues = [];
            foreach ($services as $service) {
                $returnValues[] = sprintf('"@%s"', $service['name']);
            }

            return $returnValues;
        });

        return $servicesAsParametersKeys;
    }

    public function getArgumentsFromRoute()
    {
        $argumentsFromRoute = new \Twig_SimpleFunction('argumentsFromRoute', function ($route) {
            $returnValues = '';
            preg_match_all('/{(.*?)}/', $route, $returnValues);

            $returnValues = array_map(function ($value) {
                return sprintf('$%s', $value);
            }, $returnValues[1]);

            return $returnValues;
        });

        return $argumentsFromRoute;
    }

    public function getServicesClassInitialization()
    {
        $returnValue = new \Twig_SimpleFunction('serviceClassInitialization', function ($services) {
            $returnValues = [];
            foreach ($services as $service) {
                $returnValues[] = sprintf('    $this->%s = $%s;', $service['machine_name'], $service['machine_name']);
            }

            return implode(PHP_EOL, $returnValues);
        });

        return $returnValue;
    }

    public function getServicesClassInjection()
    {
        $returnValue = new \Twig_SimpleFunction('serviceClassInjection', function ($services) {
            $returnValues = [];
            foreach ($services as $service) {
                $returnValues[] = sprintf('      $container->get(\'%s\')', $service['name']);
            }

            return implode("," . PHP_EOL, $returnValues);
        });

        return $returnValue;
    }

    public function getTagsAsArray()
    {
        $returnValue = new \Twig_SimpleFunction('tagsAsArray', function ($tags) {
            $returnValues = [];
            foreach ($tags as $key => $value) {
                $returnValues[] = sprintf('%s: %s', $key, $value);
            }

            return $returnValues;
        });

        return $returnValue;
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    public function getTranslator()
    {
        return $this->translator;
    }

    public function getFiles()
    {
        return $this->files;
    }
}
