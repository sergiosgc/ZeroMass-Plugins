<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\zeromass;

/**
 * Plugin installer class
 *
 * This class takes care of plugin installation. Plugin installation is composed of the following steps
 *
 * 1. Finding out the URL for the plugin repository
 * 2. Possibly gathering authentication information for the plugin repository
 * 3. Synchronizing the repository (network transfer)
 * 4. Reading the plugin main file and parsing dependencies
 * 5. Injecting dependencies in the installation stack (and having them fulfilled)
 * 6. Copying plugin files into the application
 * 7. Firing the plugin hooks for installation
 *
 * Plugins requiring extra installation steps should hook onto the `com.sergiosgc.pluginManager.install`
 * and `com.sergiosgc.pluginManager.installed` hooks.
 *
 * When a plugin hooks onto `com.sergiosgc.pluginManager.install` and produces no output, PluginInstaller
 * will automatically fire the `com.sergiosgc.pluginManager.installed`, which continues the installation process.
 * It follows that if your plugin hooks onto `com.sergiosgc.pluginManager.install` and does produce output,
 * it will be in charge of firing `com.sergiosgc.pluginManager.installed` whenever its installation task is done. 
 * Failing to fire the `com.sergiosgc.pluginManager.installed` hook will stop the installation process from 
 * continuing.
 */
class PluginInstaller {
    const SUBTASK_URL = 'find-repo-url';
    const SUBTASK_ASKURL = 'ask-repo-url';
    const SUBTASK_REPO_AUTH = 'ask-repo-auth';
    const SUBTASK_SYNC = 'sync-repo';
    const SUBTASK_DEPS = 'read-inject-dependencies';
    const SUBTASK_INSTALL = 'install';
    const SUBTASK_EXTERNAL_INSTALL = 'ext-install';
    protected $installationStack;
    protected $knownRepos;

    public function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.pluginManager.installed', array($this, 'doneSubTaskInstall'));
    }/*}}}*/
    public function init() {
        $this->installationStack = \com\sergiosgc\Facility::get('nosql')->get('com.sergiosgc.pf.pluginInstaller.installationStack');
        if (is_null($this->installationStack)) $this->installationStack = array();
    }
    protected function registerKnownRepository($plugin, $url) {/*{{{*/
        if (!isset($this->knownRepos)) $this->knownRepos = \com\sergiosgc\Facility::get('nosql')->get('com.sergiosgc.pf.pluginInstaller.knownRepositories');
        if (is_null($this->knownRepos)) $this->knownRepos = array();
        if (isset($this->knownRepos[$plugin])) {
            $this->knownRepos[$plugin]['url'] = $url;
        } else {
            $this->knownRepos[$plugin] = array( 'url' => $url );
        }
        \com\sergiosgc\Facility::get('nosql')->set('com.sergiosgc.pf.pluginInstaller.knownRepositories', $this->knownRepos);
        
    }/*}}}*/
    protected function getKnownRepository($plugin, $exceptionIfNotFound = true) {/*{{{*/
        if (!isset($this->knownRepos)) $this->knownRepos = \com\sergiosgc\Facility::get('nosql')->get('com.sergiosgc.pf.pluginInstaller.knownRepositories');
        if (is_null($this->knownRepos)) $this->knownRepos = array();
        if (isset($this->knownRepos[$plugin])) return $this->knownRepos[$plugin];
        if ($exceptionIfNotFound) {
            throw new \ZeroMassException('Repository for ' . $plugin . ' is not known.');
        } else {
            return null;
        }
    }/*}}}*/
    protected function getKnownRepositoryAddresses() { /*{{{*/
        if (!isset($this->knownRepos)) $this->knownRepos = \com\sergiosgc\Facility::get('nosql')->get('com.sergiosgc.pf.pluginInstaller.knownRepositories');
        if (is_null($this->knownRepos)) $this->knownRepos = array();
        $result = array();
        foreach($this->knownRepos as $plugin => $url) { 
            $result[$url['url']] = true;
        }
        return array_keys($result);
    }/*}}}*/
    protected function pushTask($task, $plugin, $replace = null) {/*{{{*/
        if ($plugin == '') throw new \ZeroMassException('Invalid plugin argument');
        if (is_array($replace)) {
            for ($i=0; $i<count($this->installationStack); $i++) {
                if ($this->installationStack[$i]['task'] == $replace['task'] &&
                    $this->installationStack[$i]['plugin'] == $replace['plugin']) break;
            }
            if ($i>=count($this->installationStack)) throw new \ZeroMassException('Installation task not found');
            $replace=$i;
        }

        $task = array(
            'task' => $task,
            'plugin' => $plugin);

        if (is_null($replace)) {
            array_unshift($this->installationStack, $task);
        } else {
            $this->installationStack[$replace] = $task;
        }

        \com\sergiosgc\Facility::get('nosql')->set('com.sergiosgc.pf.pluginInstaller.installationStack', $this->installationStack);
    }/*}}}*/
    protected function removeTask($plugin) {/*{{{*/
        foreach ($this->installationStack as $i => $task) {
            if ($task['plugin'] == $plugin) {
                unset($this->installationStack[$i]);
                $this->installationStack = array_values($this->installationStack);
                \com\sergiosgc\Facility::get('nosql')->set('com.sergiosgc.pf.pluginInstaller.installationStack', $this->installationStack);
                return;
            }
        }
        throw new \ZeroMassException('Task not found for: ' . $plugin);

    }/*}}}*/
    protected function getTasksByType($type) {/*{{{*/
        $result = array();
        foreach($this->installationStack as $task) if ($task['task'] == $type) $result[] = $task;
        return $result;
    }/*}}}*/
    protected function getTaskByPlugin($plugin) {/*{{{*/
        foreach($this->installationStack as $task) if ($task['plugin'] == $plugin) return $task;
        return null;
    }    /*}}}*/
    public function install($plugin = null) {/*{{{*/
        ini_set('max_execution_time', 3600);
        ini_set('output_buffering', 0);
        @ini_set('zlib.output_compression', 0);

        if ($plugin != '' && is_null($this->getTaskByPlugin($plugin))) $this->pushTask(PluginInstaller::SUBTASK_URL, $plugin);
        ob_start();
        $this->doNextTask();
        $taskOutput = ob_get_clean();
        if ($taskOutput == '') {
            if (count($this->installationStack)) {
                // Progress report
                ob_start();
?>
<h3>Installing plugin <?php echo $this->installationStack[count($this->installationStack) - 1]['plugin'] ?></h3>
Queued installation tasks:
<ul id="installation-progress-report-stack">
<?php foreach ($this->installationStack as $task) { ?>
<li>
<?php 
                switch ($task['task']) {
                    case self::SUBTASK_URL:
                        printf('Find repository URL for %s', $task['plugin']);
                        break;
                    case self::SUBTASK_ASKURL:
                        printf('Ask for repository URL for %s', $task['plugin']);
                        break;
                    case self::SUBTASK_REPO_AUTH:
                        printf('Ask for repository authentication credentials for %s', $task['plugin']);
                        break;
                    case self::SUBTASK_SYNC:
                        printf('Sync(fetch) repository files for %s', $task['plugin']);
                        break;
                    case self::SUBTASK_DEPS:
                        printf('Inject %s dependencies', $task['plugin']);
                        break;
                    case self::SUBTASK_INSTALL:
                        printf('Install %s', $task['plugin']);
                        break;
                    case self::SUBTASK_EXTERNAL_INSTALL:
                        printf('Install external dependency %s', $task['plugin']);
                        break;
                }
?>
</li>
<?php } ?>
</ul>
<form id="install-continue-form" method="post" action="/zeromass/plugins/install/" style="display:none">
 <input type="hidden" name="plugin-name" value="<?php echo $plugin; ?>">
 <button class="btn btn-primary" type="submit">Continue installation</button>
</form>
<script type="text/javascript">
jQuery('#install-continue-form')[0].submit();
</script>
<?php
                $taskOutput = ob_get_clean();
            } else {
                // Done
                header('Location: /zeromass/plugins/?installFinished=1');
                exit;
            }
        }
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'com.sergiosgc.pluginManagerPage.php');
        $page = new PluginManagerPage();
        $page->addBreadcrumb('Home', '/');
        $page->addBreadcrumb('ZeroMass Plugins', '/zeromass/plugins/');
        $page->setTitle(sprintf('Install'));
        $page->start();
        echo $taskOutput;
        $page->done();
    }/*}}}*/
    protected function doNextTask() {/*{{{*/
        $tasks = $this->getTasksByType(PluginInstaller::SUBTASK_SYNC);
        if (0 < count($tasks)) return $this->doSubTaskSync($tasks[0]);

        $tasks = $this->getTasksByType(PluginInstaller::SUBTASK_URL);
        if (0 < count($tasks)) return $this->doSubTaskUrl($tasks);

        $tasks = $this->getTasksByType(PluginInstaller::SUBTASK_DEPS);
        if (0 < count($tasks)) return $this->doSubTaskDeps($tasks[0]);

        $tasks = $this->getTasksByType(PluginInstaller::SUBTASK_ASKURL);
        if (0 < count($tasks)) return $this->doSubTaskAskUrl($tasks);

        $tasks = $this->getTasksByType(PluginInstaller::SUBTASK_REPO_AUTH);
        if (0 < count($tasks)) return $this->doSubTaskAskRepoAuth($tasks);

        $tasks = $this->getTasksByType(PluginInstaller::SUBTASK_EXTERNAL_INSTALL);
        if (0 < count($tasks)) return $this->doSubTaskExternalInstall($tasks);

        $tasks = $this->getTasksByType(PluginInstaller::SUBTASK_INSTALL);
        if (0 < count($tasks)) return $this->doSubTaskInstall($tasks[0]);
    }/*}}}*/
    protected function doSubTaskSync($task) {/*{{{*/
        require_once(dirname(__FILE__) . '/com.sergiosgc.repository.php');
        $knownRepo = $this->getKnownRepository($task['plugin']);
        $repository = Repository::createFromURL($knownRepo['url']);
        if (isset($knownRepo['auth'])) $repository->setAuthentication($knownRepo['auth']);
        if (!$repository->isInSync()) {
            if ($repository->requiresAuthentication()) {
                $this->pushTask(self::SUBTASK_REPO_AUTH, $task['plugin'], $task);
                return;
            }
            $repository->sync();
        }
        $this->pushTask(self::SUBTASK_DEPS, $task['plugin'], $task);
    }/*}}}*/
    protected function doSubTaskURL($tasks) {/*{{{*/
        require_once(dirname(__FILE__) . '/com.sergiosgc.repository.php');
        $repos = array();
        foreach($this->getKnownRepositoryAddresses() as $url) $repos[$url] = Repository::createFromURL($url);

        foreach ($tasks as $task) {
            $repoUrl = $this->getRepositoryUrlFromDNS($task['plugin']);
            if ($repoUrl) {
                $this->registerKnownRepository($task['plugin'], $repoUrl);
                $this->pushTask(self::SUBTASK_SYNC, $task['plugin'], $task);
                continue;
            }

            foreach ($repos as $url => $repo) if ($repo->containsPlugin($task['plugin'])) {
                $this->registerKnownRepository($task['plugin'], $url);
                $this->pushTask(self::SUBTASK_SYNC, $task['plugin'], $task);
                continue 2;
            }
            
            $this->pushTask(self::SUBTASK_ASKURL, $task['plugin'], $task);
        }
    }/*}}}*/
    protected function doSubTaskAskURL($tasks) {/*{{{*/
        $candidates = $tasks;
        $tasks = array();
        foreach ($candidates as $task) if ($this->getKnownRepository($task['plugin'], false)) {
            $this->pushTask(self::SUBTASK_SYNC, $task['plugin'], $task);
        } else {
            $tasks[] = $task;
        }
        if (count($tasks) == 0) return;
?>
<h3>Repository information needed</h3>
Please provide the repository addresses (URLs) and click "Set repository addresses":
<form method="post" action="/zeromass/plugins/install/setrepourl">
<table class="table table-striped table-bordered table-hover">
 <tr><th>Plugin</th><th>Repository URL</th></tr>
<?php foreach ($tasks as $task) { ?>
<tr><td><?php echo $task['plugin'] ?></td><td><input type="text" name="url-<?php echo $task['plugin'] ?>"></td></tr>
<?php } ?>
</table>
<button class="btn btn-primary" type="submit">Set repository addresses</button>
</form>
<?php
    }/*}}}*/
    protected function doSubTaskDeps($task) {/*{{{*/
        require_once(dirname(__FILE__) . '/com.sergiosgc.repository.php');
        $knownRepo = $this->getKnownRepository($task['plugin']);
        $repository = Repository::createFromURL($knownRepo['url']);
        if (isset($knownRepo['auth'])) $repository->setAuthentication($knownRepo['auth']);
        $plugin = $repository->createPlugin($task['plugin']);
        $dependencies = $plugin->getDependencies();
        $externalDependenciesPrefixes = array('pear_', 'phpe_');
        foreach ($dependencies as $dependency) {
            if (!is_null($this->getTaskByPlugin($dependency))) continue;

            foreach ($externalDependenciesPrefixes as $prefix) {
                if (strlen($dependency >= $prefix) && substr($dependency, 0, strlen($prefix)) == $prefix) {
                    $this->pushTask(self::SUBTASK_EXTERNAL_INSTALL, $dependency);
                    continue 2;
                }
            }
            $this->pushTask(self::SUBTASK_URL, $dependency);
        }
        $this->pushTask(self::SUBTASK_INSTALL, $task['plugin'], $task);
    }/*}}}*/
    protected function doSubTaskExternalInstall($tasks) {/*{{{*/
        // Filter out dependencies already present
        $candidates = $tasks;
        $tasks = array();
        foreach ($candidates as $task) {
            if (preg_match('/^pear_(.*)/', $task['plugin'], $matches)) {
                $package = $matches[1];
                if ($this->isPearPackageInstalled($package)) {
                    $this->removeTask($task['plugin']);
                } else {
                    $tasks[] = $task;
                }
            }
        }
        if (count($tasks) == 0) return;
        $pear = false;
        $phpExtensions = false;
        foreach($tasks as $task) {
            if (preg_match('/^pear_(.*)/', $task['plugin'])) $pear = true;
            if (preg_match('/^phpe_(.*)/', $task['plugin'])) $pear = true;
        }
        if ($pear) {
?>
<h3>External dependencies for plugin <?php echo $this->installationStack[count($this->installationStack) - 1]['plugin'] ?></h3>
Please install these PEAR packages:
<ul id="install-pear-list">
<?php foreach($tasks as $task) if (preg_match('/^pear_(.*)/', $task['plugin'], $matches)) { ?>
<li><?php echo $matches[1]; ?></li>
<?php } ?>
</ul>
<?php
        }
        if ($phpExtensions) {
?>
Please install these PHP extensions:
<ul id="install-phpe-list">
<?php foreach($tasks as $task) if (preg_match('/^phpe_(.*)/', $task['plugin'], $matches)) { ?>
<li><?php echo $matches[1]; ?></li>
<?php } ?>
</ul>
<?php
        }
?>
<p>After all external dependencies are installed, please click "Continue installation".</p>
<form id="install-continue-form" method="post" action="/zeromass/plugins/install/">
 <input type="hidden" name="plugin-name" value="<?php echo $this->installationStack[count($this->installationStack) - 1]['plugin']; ?>">
 <button class="btn btn-primary" type="submit">Continue installation</button>
</form>
<?php
    }/*}}}*/
    protected function doSubTaskInstall($task) {/*{{{*/
        require_once(dirname(__FILE__) . '/com.sergiosgc.repository.php');
        $knownRepo = $this->getKnownRepository($task['plugin']);
        $repository = Repository::createFromURL($knownRepo['url']);
        if (isset($knownRepo['auth'])) $repository->setAuthentication($knownRepo['auth']);
        $plugin = $repository->createPlugin($task['plugin']);
        $this->pluginBeingInstalled = $task['plugin'];
        $plugin->install();
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.pluginManager.installed', $task['plugin']);
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.pluginManager.installed_' . $task['plugin']);
    }/*}}}*/

    protected function isPearPackageInstalled($package) {/*{{{*/
        $pearDir = stream_resolve_include_path('System.php');
        if ($pearDir === false) return false; // PEAR itself is not installed
        $pearDir = dirname($pearDir);
        $packageStartFile = $pearDir . '/' . strtr($package, '_', '/') . '.php';
        return file_exists($packageStartFile);
    }/*}}}*/
    protected function getRepositoryUrlFromDNS($plugin) {/*{{{*/
        require_once('Net/DNS2.php');
        $resolver = new \Net_DNS2_Resolver();
        $domain = implode('.', array_reverse(explode('.', $plugin)));
        $candidates = array();
        do {
            array_push($candidates, $domain);
            $domain = preg_replace('_^[^.]*\.?_', '', $domain);
        } while ($domain != '');
        try {
            $candidate = 'sergiosgc.com';
            $result = $resolver->query($candidate, 'TXT');
            foreach($result->answer as $txtRecord) foreach($txtRecord->text as $txtRecordText) {
                if (preg_match('_^com.sergiosgc.zeromass:((http|https|ftp|sftp|git|file)://.*)_', $txtRecordText, $matches)) {
                    return $matches[1];
                }
            }
        } catch (\Exception $e) {
            throw new \ZeroMassException($e);
        }
        return false;
    }/*}}}*/
    public function doneSubTaskInstall($plugin) {/*{{{*/
        $this->removeTask($plugin);
    }/*}}}*/
    protected function setRepositoryURLs($repos) {/*{{{*/
        foreach($repos as $plugin => $url) $this->registerKnownRepository($plugin, $url);
        header('Location: /zeromass/plugins/install/');
        exit;
    }/*}}}*/
}
?>
