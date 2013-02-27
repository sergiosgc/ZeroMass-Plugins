<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\zeromass;
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'com.sergiosgc.plugin.php');

class PluginManager {
    /**
     * Constructor
     */
    public function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_switchboard', array($this, 'addSwitchboardHandlers'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.plugin.phpdoc.getallSourceFiles', array($this, 'filterOutLibsFromDoc'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.after__construct', array($this, 'registerBackupAPIProviders'));
    }/*}}}*/
    public function init() {/*{{{*/
    }/*}}}*/
    public function addSwitchboardHandlers() {/*{{{*/
        $switchboard = \com\sergiosgc\Facility::get('switchboard');

        $switchboard->addHandler(array($this, 'install'), '/zeromass/plugins/install/');
        $switchboard->addHandler(array($this, 'install'), '_/zeromass/plugins/install/\?.*_', \com\sergiosgc\Switchboard::TARGET_TYPE_URIREGEX);
        $switchboard->addHandler(array($this, 'listPlugins'), '/zeromass/plugins/');
        $switchboard->addHandler(array($this, 'listPlugins'), '/zeromass/plugins/?installFinished=1');
        $switchboard->addHandler(array($this, 'pluginDetail'), '_/zeromass/plugin/[^/]+$_', \com\sergiosgc\Switchboard::TARGET_TYPE_URIREGEX);
        $switchboard->addHandler(array($this, 'setRepositoryURLs'), '/zeromass/plugins/install/setrepourl');
    }/*}}}*/
    protected function listPluginSorter($a, $b) {
        if ($a->getId() == $b->getId()) return 0;
        if ($a->getId() < $b->getId()) return -1;
        return 1;
    }

    /**
     * Output the plugin list page
     *
     * This method handles the /zeromass/plugins/ page. It lists all the plugins and provides a form for plugin installation
     *
     * @return boolean Always true signalling a successfull handling of the page URL
     */
    public function listPlugins() {/*{{{*/
        $plugins = Plugin::createAllPlugins();
        /*# 
         * Allow for plugins to be added or removed programatically, after scanning the plugin directory
         *
         * @param array \com\sergiosgc\zeromass\Plugin instance array
         * @return array \com\sergiosgc\zeromass\Plugin instance array
         */
        $plugins = \ZeroMass::getInstance()->do_callback('com.sergiosgc.zeromass.pluginManager.listPlugins', $plugins);
        usort($plugins, array($this, 'listPluginSorter'));
        $names = array();
        foreach ($plugins as $plugin) $names[] = $plugin->getName();
        $table = new \com\sergiosgc\ui\Table();
        foreach ($plugins as $plugin) $table->addRow(
            array(
                'plugin' => sprintf('<span class="plugin-list-name">%s</span><br><a href="/zeromass/plugin/%s">%s</a>', $plugin->getId(), $plugin->getId(), $plugin->getName())
            )
        );

        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'com.sergiosgc.pluginManagerPage.php');
        $page = new PluginManagerPage();
        $page->addBreadcrumb('Home', '/');
        $page->addBreadcrumb('ZeroMass Plugins', '/zeromass/plugins/');
        $page->setTitle(sprintf('Plugin list'));
        $page->start();
        if (isset($_REQUEST['installFinished'])) {
?>
<div class="alert alert-success">
 <button type="button" class="close" data-dismiss="alert">&times;</button>
 <strong>Finished!</strong> All installation tasks executed successfully.
</div>
<?php
        }
        $table->output();
?>
<form method="post" action="/zeromass/plugins/install/" class="form-inline">
 <fieldset>
  <input type="text" name="plugin-name" placeholder="Plugin name" />
  <button type="submit" class="btn btn-primary">Install plugin</button>
 </fieldset>
</form>
<?php
        $page->done();
        return true;
    }/*}}}*/
    public function pluginDetail() {/*{{{*/
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.permission', '');
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'com.sergiosgc.pluginManagerPage.php');
        preg_match('_[^/]+$_', $_SERVER['REQUEST_URI'], $matches);
        $pluginId = $matches[0];
        $plugin = Plugin::createById($pluginId);
        $page = new PluginManagerPage();
        $page->setTitle(sprintf('Plugin details: %s', $plugin->getName()));
        $page->addBreadcrumb('Home', '/');
        $page->addBreadcrumb('ZeroMass Plugins', '/zeromass/plugins/');
        $page->addBreadcrumb($plugin->getName(), $_SERVER['REQUEST_URI']);
        $page->start();
?>
<div>
 <ul class="nav nav-tabs">
  <li><a href="#">Description</a></li>
  <li class="active"><a href="#">Usage summary</a></li>
  <li><a href="#">PHPDoc</a></li>
  <li><a href="#">Hooks</a></li>
 </ul>
 <div class="tabs">
  <div style="display: none" class="plugin-detail">
   <div class="plugin-detail-name"><strong>Plugin name: </strong><?php echo $plugin->getName() ?></div>
   <div class="plugin-detail-author"><strong>Author: </strong><?php echo $plugin->getAuthor() ?></div>
   <div class="plugin-detail-copyright"><strong>Copyright: </strong><?php echo $plugin->getCopyright() ?></div>
   <div class="plugin-detail-version"><strong>Version: </strong><?php echo $plugin->getVersion() ?></div>
   <div class="plugin-detail-description-short"><strong>Description</strong><br><?php echo $plugin->getShortDescription() ?></div>
  </div>
  <div id="usage-summary">
<?php echo $plugin->getUsageSummary() ?>
  </div>
  <div style="display: none" id="php-doc">
<?php echo $plugin->getPHPDoc() ?>
  </div>
  <div style="display: none" id="hooks-doc">
<?php echo $plugin->getHooksDoc() ?>
  </div>
 </div>
<script type="text/javascript">
$('.nav-tabs a').bind("click", function(ev) {
 var navTabs = ev.currentTarget.parentNode.parentNode;
 var tabLinks = $(navTabs).find('a');
 var activeLink;
 for(var candidate = 0; candidate < tabLinks.length; candidate++) if (tabLinks[candidate] == ev.currentTarget) activeLink=candidate;
 var tabDivs = navTabs.nextSibling;
 while (tabDivs.nodeType != 1 && tabDivs.nodeName != 'div' && tabDivs.nodeName != 'DIV') tabDivs = tabDivs.nextSibling;
 $(navTabs).find('li').removeClass('active');
 $(ev.currentTarget.parentNode).addClass('active');

 $(tabDivs).find('> div').hide();
 $($(tabDivs).find('> div')[activeLink]).show();
});
</script>
</div>
<?php
        $page->done();
        return true;
    }/*}}}*/
    /**
     * Process the com.sergiosgc.zeromass.plugin.phpdoc.getallSourceFiles filter
     *
     * Remove the library files from the list of source files to be processed for documentation generation
     *
     * @param array Array of filenames (strings) to be processed
     * @return array Array of filenames (strings) to be processed
     */
    public function filterOutLibsFromDoc($files) {/*{{{*/
        for ($i=count($files) - 1; $i>=0; $i--) if (preg_match('_(lib/markdown.php$)|(lib/phpdoc.php$)_', $files[$i])) unset($files[$i]);
        $files = array_values($files);
        return $files;
    }/*}}}*/
    public function registerBackupAPIProviders()/*{{{*/
    {
        if (is_null(\com\sergiosgc\Facility::get('cache', false))) {
            require_once(dirname(__FILE__) . '/com.sergiosgc.cache.php');
            \com\sergiosgc\Facility::register('cache', Cache::getInstance());
        }
        if (is_null(\com\sergiosgc\Facility::get('nosql', false))) {
            require_once(dirname(__FILE__) . '/com.sergiosgc.nosql.php');
            \com\sergiosgc\Facility::register('nosql', Nosql::getInstance());
        }
    }/*}}}*/
    public function install() {/*{{{*/
        require_once(dirname(__FILE__) . '/com.sergiosgc.pluginInstaller.php');
        if (isset($_REQUEST['plugin-name'])) {
            $_REQUEST['plugin-name'] = preg_replace('_[^a-z0-9.]_', '', $_REQUEST['plugin-name']); // Prevent injection attacks, sanitize input
            (new PluginInstaller())->install($_REQUEST['plugin-name']);
        } else {
            (new PluginInstaller())->install();
        }

        return true;
    }/*}}}*/
    public function setRepositoryURLs() {/*{{{*/
        require_once(dirname(__FILE__) . '/com.sergiosgc.pluginInstaller.php');
        $setRepos = array();
        foreach($_REQUEST as $key => $value) {
            if ($value != '' && preg_match('_^url-(.*)_', $key, $matches)) $setRepos[strtr($matches[1], '_', '.')] = $value;
        }
        (new PluginInstaller())->setRepositoryURLs($setRepos);
        return true;
    }/*}}}*/
}
new PluginManager();
/*#
 * ZeroMass PluginManager
 *
 * PluginManager allows you to manage plugins inside your ZeroMass installation: Install, update, remove, configure. It 
 * also allows you to read the documentation parsed from the plugins source file.
 *
 * # Usage summary
 *
 * The ZeroMass plugin manager provides an user interface service for managing plugins. It does not provide a programatic
 * interface (i.e. it is not designed to be a library integrated into your own projects).
 *
 * ## A bit of background
 *
 * The Pluggable Web Framework provides two core services: 
 *
 *  - A hook API
 *  - A plugin loading service
 *  - An API slotting service
 *
 * The hook API, interesting as it is, is not the target of the ZeroMass Plugin manager. The plugin loading service is. 
 * ZeroMass will load any plugin present in the plugin directory (zeromass/plugins in the root directory of the web application). 
 * Plugins, as per the ZeroMass definition, may composed of one PHP file, dropped in the plugin directory, or a directory 
 * with multiple files, dropped also in the plugin directory. ZeroMass will scan the plugin directory and:
 *
 *  - Load any PHP file in the first level of the plugin directory (i.e. it will not scan the directory recursively)
 *  - For every directory in the first level of the plugin directory, load the PHP file, inside that directory, with the 
 * same name as the directory. For example, if it finds zeromass/plugins/example/, it will load zeromass/plugins/example/example.php
 *
 * The API slotting service is a simple feature. It allows plugins to register for being the application-wide provider of some
 * known API. The basic case is the 'switchboard' API, which allows for HTTP requests to be answered by a method of a plugin. 
 * Regardless of which plugin actually implements the Switchboard service, clients may request the service from the ZeroMass class and 
 * be handed an instance of a Switchboard.
 *
 * However, beyond the core services, there are a lot of plugin management needs...
 *
 * ## What is it that PluginManager does?
 *
 * Plugin management, beyond the programatic tasks handled by the ZeroMass core, is:
 *
 *  - Discoverability: The notion of plugin repository and the ability to list plugins from known repositories
 *  - Installation: The ability to pull a plugin from a repository and install it: 
 *      - Dependency handling: Handling of dependencies on PHP extension, PEAR packages and ZeroMass plugins
 *      - Installation tasks: Initial plugin configuration and environment creation, namely database creation
 *  - Updates: Update detection and the ability to update a plugin
 *  - Uninstallation: The reverse of the above
 *  - Documentation: This text you are reading now, extracted from the plugin source code, Perl POD-style and PHPdoc style
 *
 * ## Repositories
 *
 * ### Creating a repository
 *
 * A plugin repository is quite simple to create. It is a "plugin directory" just like the one on ZeroMass web applications, accessible
 * over the internet, or accessible on the local filesystem. ZeroMass PluginManager is capable of accessing repositories over:
 *
 *  - HTTP/HTTPS (not implemented yet)
 *  - FTP (not implemented yet)
 *  - SFTP (not implemented yet)
 *  - SSH (not implemented yet)
 *  - GIT (not implemented yet)
 *
 * A repository is defined by an URL pointing at the repository directory. Examples: 
 *
 *     http://example.com/~johndoe/zeromass/
 *     ftp://example.com/~johndoe/zeromass/
 *     git://example.com/~johndoe/zeromass/
 *     file:///tmp/examplerepo/
 *
 * If authentication is needed to access the repository, it may be included in the URL:
 *
 *     http://johndoe:1234@example.com/~johndoe/zeromass/
 *     sftp://johndoe:1234@example.com/~johndoe/zeromass/
 *
 * Authentication may also be provided separately in the repository configuration inside the ZeroMass PluginManager (List Repositories > 
 * Edit in the navigation menu).
 *
 * ### Repositories and DNS
 *
 * Repositories may be announced via the Domain Name System. Plugins must be namespaced, and the plugin namespace is a reversed domain 
 * name. For example, this plugin is named `com.sergiosgc.pluginManager`, a reversion of `pluginManager.sergiosgc.com`. You may, at any 
 * subdomain defined by the plugin name, create a DNS record announcing the repository where the plugin is available. For the example 
 * of the `com.sergiosgc.pluginManager` plugin, DNS records may be published for: 
 *
 *  - `sergiosgc.com`, or
 *  - `pluginManager.sergiosgc.com`
 *
 * A ZeroMass repository record is a TXT record (DNS type 16), with the string `com.sergiosgc.zeromass`, followed by a colon, followed by the 
 * repository URL. For our example, assuming that the repository URL is `http://example.com/~johndoe/zeromass/`, the DNS record content would be
 *
 *     com.sergiosgc.zeromass:http://example.com/~johndoe/zeromass/
 *
 * ### Installing repositories
 *
 * To install a repository, access the ZeroMass PluginManager on your web application, typically at `http://<app_domain>/zeromass/plugins/`, 
 * click `List Repositories` on the main menu, and add the repository URL to the list. Packages from the new repository will appear 
 * in the installable plugin list.
 *
 * Note that, if a repository is properly announced via DNS, it may be unnecessary to add it explicitly. When installing a plugin
 * that is not available at known repositories, the PluginManager will look for repository information via DNS and will automatically 
 * add the repository to its known repository list. Naturally, the plugin must be named explicitely, not picked from the installable 
 * plugin list.
 *
 * ### Installing plugins
 *
 * You may install plugins using the user interface. If you know the plugin name (e.g. `com.sergiosgc.pluginManager`):
 *
 *  1. Access the ZeroMass Plugin Manager, typically at `http://<app_domain>/zeromass/plugins/`
 *  2. Click `List Plugins` on the left side menu bar
 *  3. Fill in the `Plugin name` box at the bottom and click `Install`
 *
 * If the plugin repository is already known, or properly announced via DNS, the PluginManager will fetch and install the plugin. Otherwise,
 * you will be asked for the repository URL where the plugin can be found. 
 *
 * You may also install plugins from the plugin listing interface. You can list plugins from known repositories from the `List repositories`
 * menu entry on the ZeroMass Plugin Manager main menu (accessible usually at `http://<app_domain>/zeromass/plugins/`). Next to each repository there
 * is a `Browse` link that will list the repository's plugins, allowing for plugin installation using the `Install` link next to each plugin.
 *
 * Depending on the plugin, you may be presented with additional pages for plugin installation.
 *
 * #### For plugin developers
 *
 * If your plugin requires additional installation tasks, you should add a handler for the `com.sergiosgc.pluginManager.install_X` action, 
 * where `X` is your plugin name. You may execute only programatic actions, or you may present a page for user interaction (to gather database 
 * credentials for example). When your plugin installation is done, call the `com.sergiosgc.pluginManager.installed_X` action, where again 
 * `X` is your plugin name.
 *
 * If your plugin has dependencies, you may assume those will be installed and configured by the time your plugin is called, via the `com.sergiosgc.pluginManager.install_X`
 * hook.
 *
 * As an example, if your plugin is called `com.example.blinkenlights`, you should register for `com.sergiosgc.pluginManager.install_com.example.blinkenlights`
 * and when done, you should call `com.sergiosgc.pluginManager.installed_com.example.blinkenlights`:
 *
 *     \ZeroMass::getInstance()->register_callback('com.sergiosgc.pluginManager.install_com.example.blinkenlights', array('BlinkenLights', install);
 *     ...
 *     class BlinkenLights {
 *         ...
 *         public static function install() {
 *             // Do something 
 *             ...
 *             \ZeroMass::getInstance()->do_callback('com.sergiosgc.pluginManager.installed_com.example.blinkenlights');
 *             return; // Immediatly return
 *         }
 *         ...
 *     }
 *
 * ### Dependency handling
 *
 * A plugin may depend on other plugins, on PHP extensions and/or on PEAR packages. This is handled transparently by the ZeroMass PluginManager. When 
 * installing a plugin with dependencies, dependencies will be installed prior to installing the plugin. Naturally, this operation is recursive, 
 * potentially leading to deep dependency trees getting installed. 
 *
 * While dependencies on other ZeroMass plugins can be handled automatically, dependencies on PHP extensions and on PEAR packages cannot. Prior to plugin 
 * installation, you will be instructed to install any PEAR dependencies and any PHP extensions needed.
 *
 * #### For plugin developers
 *
 * For dependency handling by the ZeroMass PluginManager, you will need to declare any dependencies of your plugin. Dependencies are declared in the 
 * main ZeroMass DocBlock (either at the top or at the bottom of the main plugin file). Dependencies are declared with the `@depends` tag.
 *
 * Dependencies on other ZeroMass plugins are declared with `@depends full_plugin_name`. Example:
 *  
 *     /*#
 *     ...
 *      * @depends com.example.electricity
 *     ...
 *      *\/
 *
 * Dependencies on PEAR packages are declared with `@depends pear_[pear package name]`. Example: 
 *
 *     /*#
 *     ...
 *      * @depends pear_XML_RPC2
 *     ...
 *      *\/
 *
 * Dependencies on PHP extensions are declared with `@depends phpe_[extension name]`. Example:
 *
 *     /*#
 *     ...
 *      * @depends phpe_xml-rpc
 *     ...
 *      *\/
 *
 * ### Documentation (for plugin developers)
 *
 * ZeroMass PluginManager generates documentation for plugins, from comments in the plugin source code. Usage documentation is generated from special ZeroMass 
 * DocBlocks and API documentation is generated from [PHPDoc](http://www.phpdoc.org/) DocBlocks.
 *
 * Regular [PHPDoc](http://www.phpdoc.org/) DocBlocks are parsed and presented in the plugin page, under the PHPDoc tab. Note that, 
 * since this generated documentation is for plugin users, not plugin developers, only publicly accessible methods and variables are 
 * presented. Internal mechanics of the plugin are of no use to plugin users and not presented. To put it another way, this feature is not a 
 * replacement for regular PHPDoc-generated documentation. Refer to [PHPDoc documentation](http://www.phpdoc.org/docs/latest/index.html) for further info.
 *
 * ZeroMass DocBlocks are similar to PHPDoc DocBlocks. A PHP DocBlock begins with the string `/**`. A ZeroMass DocBlock begins with the string `/*#`. There are two types
 * of ZeroMass DocBlocks: 
 *
 * 1. The main ZeroMass DocBlock
 * 2. Hook documentation ZeroMass DocBlocks
 *
 * #### Main ZeroMass DocBlock
 *
 * You must have one main ZeroMass DocBlock per plugin. It must appear on the main file of the plugin (either `pluginName.php` or `pluginName/pluginName.php`). It 
 * must appear, inside the main file, either at the top (PHPDoc style) or at the bottom of the plugin (Perl POD style). If at the top, it must appear before the 
 * first block (before the first open bracket `{`), if at the bottom, it must appear after the last block (after the last close bracket `}`). 
 *
 * The text inside the main ZeroMass DocBlock is meant to be a plugin usage primer, much like the text you are reading now. The text will be interpreted as Markdown. 
 * The Markdown syntax is readable in source form, and produces acurate HTML when processed. The Markdown syntax is available on John Gruber's 
 * [Daring Fireball](http://daringfireball.net/projects/markdown/syntax). The only Markdown syntax addition used is an extra 
 * [backslash escape](http://daringfireball.net/projects/markdown/syntax#backslash) for the `/` character (needed to type `*\/` without closing the PHP comment).
 *
 * Beyond the Markdown text, the main ZeroMass DocBlock may contain tags, much as PHPDoc tags. The PluginManager recognizes:
 *
 *  - `author`
 *  - `copyright`
 *  - `example`
 *  - `link`
 *  - `version`
 *  - `depends`
 *
 * A tag must occur in an empty line of the DocBlock, preceded by an `@` character, much as in regular DocBlocks. Example: 
 *
 *     /*#
 *     ...
 *      * @version 1.0
 *     ...
 *     *\/
 *
 * The `depends` tag was discussed in the previous section. The `version` tag is used to decide if plugins need to be upgraded. The comparison is done with PHP's 
 * [version_compare](http://php.net/manual/en/function.version-compare.php) function, so you should code your versions expecting this behaviour. All other tags 
 * are freeform and presentational only. 
 *
 * #### Hook documentation
 *
 * ZeroMass PluginManager will extract hook usage documentation from your plugin. Calls to these functions will produce documentation:
 *
 *  - `ZeroMass::register_callback`
 *  - `add_filter`
 *  - `add_action`
 *  - `ZeroMass::do_callback`
 *  - `apply_filters`
 *  - `do_action`
 *
 * When calling these two methods
 *
 *  - `ZeroMass::register_callback`
 *  - `ZeroMass::do_callback`
 *
 * get the ZeroMass singleton instance using `\ZeroMass::getInstance()` and call the method imediately. Do not store the instance yourself. For example, this code should 
 * be avoided:
 *
 *     $zeromass = \ZeroMass::getInstance();
 *     $zeromass->do_callback('example');
 *
 * the code should instead be:
 *
 *     \ZeroMass::getInstance()->do_callback('example');
 *
 * otherwise, statically analyzing the code and proving it would correspond to a call to `ZeroMass::do_callback` could easily result in 
 * a [Halting Problem](http://en.wikipedia.org/wiki/Halting_problem). Statical analysis is very simplistic. Calls in the wrong format will be ignored.
 *
 * On calls to the documentable functions, avoid passing in the tag as a variable. For example, this code should be avoided:
 *
 *     $action = 'com.example.floobadoo';
 *     do_action($action);
 *
 * and should instead be:
 *
 *     do_action('com.example.floobadoo');
 *
 * as the action won't be statically parsed, and the call will not be used for documentation generation purposes. When a hook tag is parameterized, like
 * `com.sergiosgc.pluginManager.install_X` referred above, you may use an expression starting with the fixed part as a string. Example: 
 *
 *     \ZeroMass::getInstance()->do_callback('com.sergiosgc.pluginManager.installed_' . $this->getPluginName());
 *
 * You may add a ZeroMass DocBlock, in Markdown syntax, explaining the usage of a hook exported by the plugin. Do so in one of the calls to these functions: 
 *
 *  - `ZeroMass::do_callback`
 *  - `apply_filters`
 *  - `do_action`
 *
 * For example: 
 * 
 *      /*#
 *       * The `com.sergiosgc.pluginManager.install_` hook allows plugins to run installation tasks 
 *       * right after being physically installed on a new system.
 *       *\/
 *      \ZeroMass::getInstance()->do_callback('com.sergiosgc.pluginManager.install_' . $justInstalledPluginName);
 *
 * These ZeroMass DocBlocks accept the `@param` and `@return` tags for documentation, just like regular function PHP DocBlocks.
 *
 * If a hook is called more than once, and documented more than once, the result is not defined. ZeroMass PluginManager will pick one of the DocBlocks. Translation:
 * document each hook only once or risk undocumented behaviour. 
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
