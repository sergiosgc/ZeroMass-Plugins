<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\zeromass;
class Plugin {
    protected $sourceFile;
    protected $name;
    protected $shortDescription;
    protected $usageSummary;
    protected $knownDocblockTags = array('author', 'copyright', 'example', 'link', 'version', 'depends');
    protected $docBlockTagValues = array();
    public function __construct($sourceFile) {/*{{{*/
        $this->sourceFile = $sourceFile;
    }/*}}}*/
    public static function createById($id) { /*{{{*/
        $pluginDir = \ZeroMass::getInstance()->pluginDir;
        $candidates = array();
        $candidates[] = $pluginDir . DIRECTORY_SEPARATOR . $id . '.php';
        $candidates[] = $pluginDir . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . $id . '.php';
        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_file($candidate)) return new Plugin($candidate);
        }
        throw new \ZeroMassNotFoundException($id . ' plugin not found in plugin directory ' . \ZeroMass::getInstance()->pluginDir);
    }/*}}}*/
    public static function createAllPlugins() {/*{{{*/
        $result = array();
        $pluginDirHandle = opendir(\ZeroMass::getInstance()->pluginDir);
        if ($pluginDirHandle === false) throw new \ZeroMassException('Unable to open plugin directory: ' . \ZeroMass::getInstance()->pluginDir);
        while (($file = readdir($pluginDirHandle)) !== false) {
            if ($file == '.' || $file == '..') continue;

            $fullPath = \ZeroMass::getInstance()->pluginDir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($fullPath)) {
                $tentativePluginFile = $fullPath . DIRECTORY_SEPARATOR . $file . '.php';
                if (is_file($tentativePluginFile) && is_readable($tentativePluginFile)) $result[] = $tentativePluginFile;
            } else {
                if (strlen($file) > 4 && substr($file, -4) == '.php') $result[] = $fullPath;
            }
        }
        foreach ($result as $i => $source) $result[$i] = new Plugin($source);
        return $result;
    }/*}}}*/
    public function getName() {/*{{{*/
        if (!isset($this->name)) $this->parseSource();
        if (is_null($this->name)) $this->name = preg_replace('_\.php$_', '', basename($this->sourceFile));
        return $this->name;
    }/*}}}*/
    public function getShortDescription() {/*{{{*/
        if (!isset($this->shortDescription)) $this->parseSource();
        if (is_null($this->shortDescription)) $this->shortDescription = 'No plugin description defined in plugin source';
        return $this->shortDescription;
    }/*}}}*/
    public function getId() {/*{{{*/
        $parts = explode(DIRECTORY_SEPARATOR, $this->sourceFile);
        $result = preg_replace('_\\.php$_', '', $parts[count($parts) - 1]);
        return $result;
    }/*}}}*/
    protected function parseSource() {/*{{{*/
        $tokens = token_get_all(file_get_contents($this->sourceFile));
        foreach ($tokens as $i => $token) if (is_array($token)) $tokens[$i][3] = token_name($token[0]);
        $this->parseMainDocBlock($tokens);
        
    }/*}}}*/
    protected function parseMainDocBlock($tokens) {/*{{{*/
        $mainDocBlock = null;
        $i=0;
        while (is_null($mainDocBlock) && $i < count($tokens)) {
            if (is_string($tokens[$i]) && $tokens[$i] == "{") {
                $mainDocBlock = false;
            } else if (is_array($tokens[$i]) && $tokens[$i][0] == T_COMMENT && substr($tokens[$i][1], 0, 3) == '/*#') {
                $mainDocBlock = $tokens[$i][1];
            }
            $i++;
        }
        if ($mainDocBlock === false || is_null($mainDocBlock)) {
            $mainDocBlock = null;
            $i=count($tokens)-1;
            while (is_null($mainDocBlock) && $i >= 0) {
                if (is_string($tokens[$i]) && $tokens[$i] == "}") {
                    $mainDocBlock = false;
                } else if (is_array($tokens[$i]) && $tokens[$i][0] == T_COMMENT && substr($tokens[$i][1], 0, 3) == '/*#') {
                    $mainDocBlock = $tokens[$i][1];
                }
                $i--;
            }
        }
        if ($mainDocBlock === false || is_null($mainDocBlock)) return;
        $mainDocBlock = explode("\n", $mainDocBlock);
        foreach ($mainDocBlock as $no => $line) {
            $line = preg_replace('_^/\*#|\*/_', '', $line);
            $line = preg_replace('_^\s*\*\s?_', '', $line);
            $line = preg_replace('_\s+$_', '', $line);

            $mainDocBlock[$no] = $line;
        }
        while (count($mainDocBlock) && $mainDocBlock[0] == "") { 
            unset($mainDocBlock[0]);
            $mainDocBlock = array_values($mainDocBlock);
        }
        while (count($mainDocBlock) && $mainDocBlock[count($mainDocBlock) - 1] == "") { 
            unset($mainDocBlock[count($mainDocBlock) - 1]);
        }

        /* Consume known @ lines */
        for ($line = count($mainDocBlock) - 1; $line >= 0; $line--) {
            foreach ($this->knownDocblockTags as $tag) {
                if (preg_match('_^\s*@' . $tag . '\s*(.*)$_', $mainDocBlock[$line], $matches)) {
                    if (isset($this->docBlockTagValues[$tag]) && !is_array($this->docBlockTagValues[$tag])) $this->docBlockTagValues[$tag] = array($this->docBlockTagValues[$tag]);
                    if (isset($this->docBlockTagValues[$tag])) {
                        $this->docBlockTagValues[$tag][] = $matches[1];
                    } else {
                        $this->docBlockTagValues[$tag] = $matches[1];
                    }
                    unset($mainDocBlock[$line]);
                    continue 2;
                }
            }
        }

        $start = min(1, count($mainDocBlock) - 1);
        while (count($mainDocBlock) > $start && $mainDocBlock[$start] == "") $start++;
        $end = $start;
        while (count($mainDocBlock) > $end + 1 && $mainDocBlock[$end + 1] != "") $end++;
        $this->shortDescription = '';
        for ($i=$start; $i<=$end && isset($mainDocBlock[$i]); $i++) $this->shortDescription .= $mainDocBlock[$i] . "\n";

        $start = $end+1;
        $end = count($mainDocBlock) - 2;
        if ($start < $end) {
            $this->usageSummary = '';
            for ($i=$start; $i<=$end && isset($mainDocBlock[$i]); $i++) $this->usageSummary .= $mainDocBlock[$i] . "\n";
        }

        if (count($mainDocBlock) == 0) return;
        $this->name = $mainDocBlock[0];
    }/*}}}*/
    protected function getDocBlockTagValue($tag) {/*{{{*/
        return isset($this->docBlockTagValues[$tag]) ? $this->docBlockTagValues[$tag] : null;
    }/*}}}*/
    public function getAuthor() {/*{{{*/
        return strtr($this->getDocBlockTagValue('author'), array('<' => '&lt;', '>' => '&gt;'));
    }    /*}}}*/
    public function getCopyright() {/*{{{*/
        return $this->getDocBlockTagValue('copyright');
    }    /*}}}*/
    public function getExample() {/*{{{*/
        return $this->getDocBlockTagValue('example');
    }    /*}}}*/
    public function getLink() {/*{{{*/
        return $this->getDocBlockTagValue('link');
    }    /*}}}*/
    public function getVersion() {/*{{{*/
        return $this->getDocBlockTagValue('version');
    }    /*}}}*/
    public function getUsageSummary() {/*{{{*/
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/markdown.php');
        if (!isset($this->usageSummary)) $this->parseSource();
        return \Markdown(strtr(
            $this->usageSummary, 
            array('*\\/' => '*/'))
        );
    }    /*}}}*/
    public function getAllSourceFiles() {/*{{{*/
        $result = null;

        $candidate = $this->getId() . DIRECTORY_SEPARATOR . $this->getId() . '.php';
        if ($candidate == substr($this->sourceFile, -strlen($candidate))) {
            // Directory-contained plugin
            $dir = substr($this->sourceFile, 0, -strlen(DIRECTORY_SEPARATOR . $this->getId() . '.php'));
            $result = $this->getAllFilesInDirectory($dir, '.php');
        } else {
            $candidate = $this->getId() . '.php';
            if ($candidate == substr($this->sourceFile, -strlen($candidate))) {
                $result = array($this->sourceFile);
            }
        }
        if (!is_null($result)) {
            /*#
             * Allow plugin source file list to be mangled
             */
            return \ZeroMass::getInstance()->do_callback('com.sergiosgc.zeromass.plugin.getAllSourceFiles', $result);
        }
        throw new \ZeroMassException('Unexpected plugin file structure');
    }/*}}}*/
    public function getAllFiles() {/*{{{*/
        $result = null;

        $candidate = $this->getId() . DIRECTORY_SEPARATOR . $this->getId() . '.php';
        if ($candidate == substr($this->sourceFile, -strlen($candidate))) {
            // Directory-contained plugin
            $dir = substr($this->sourceFile, 0, -strlen(DIRECTORY_SEPARATOR . $this->getId() . '.php'));
            $result = $this->getAllFilesInDirectory($dir);
        } else {
            $candidate = $this->getId() . '.php';
            if ($candidate = substr($this->sourceFile, -strlen($candidate))) {
                $result = array($this->sourceFile);
            }
        }
        if (!is_null($result)) {
            /*#
             * Allow plugin file list to be mangled
             */
            return \ZeroMass::getInstance()->do_callback('com.sergiosgc.zeromass.plugin.getAllFiles', $result);
        }
        throw new \ZeroMassException('Unexpected plugin file structure');
    }/*}}}*/
    protected function getAllFilesInDirectory($dir, $extension = null) {/*{{{*/
        $result = array();
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry == '.' || $entry == '..') continue;
            $filepath = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($filepath)) {
                $result = array_merge($result, $this->getAllFilesInDirectory($filepath, $extension));
            } else {
                if (is_null($extension) || substr($entry, -strlen($extension)) == $extension) $result[] = $filepath;
            }
        }
        return $result;
    }/*}}}*/
    public function getPHPDoc() {/*{{{*/
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/markdown.php');
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/phpdoc.php');
        $phpDocParser = new \com\sergiosgc\phpdoc\PhpParser();

        $pluginFiles = $this->getAllSourceFiles();
        /*#
         * Allow for the list of plugin files to be documented to be mangled
         */
        $pluginFiles = \ZeroMass::getInstance()->do_callback('com.sergiosgc.zeromass.plugin.phpdoc.getallSourceFiles', $pluginFiles);
        $phpDoc = array();
        foreach ($pluginFiles as $file) {
            $phpDoc = $phpDocParser->parseFile($file, $phpDoc);
        }
        if (!isset($phpDoc['classes'])) $phpDoc['classes'] = array();
        if (!isset($phpDoc['namespaces'])) $phpDoc['namespaces'] = array();
        ob_start();
?>
<ul class="functionList">
<?php foreach ($phpDoc['namespaces'] as $namespaceName => $namespace) foreach ($namespace['functions'] as $functionName => $functionProperties) { ?>
<li><?php echo $namespaceName . $functionName ?>(<?php $sep = ''; foreach ($functionProperties['arguments'] as $argName => $argProperties) {
    echo $sep . $argName; $sep = ', ';
}
?>)
</li>
<?php } ?>
</ul>
<ul class="classList">
<?php foreach ($phpDoc['classes'] as $name => $properties) { ?>
<li id="class-<?php echo strtr($name, '\\', '-') ?>"><a href="#" onclick="jQuery('#inner-' + this.parentNode.id).toggle(); return false;"><?php echo $name ?></a><br>
<?php print($properties['summary']); ?>
<div style="display: none" id="inner-class-<?php echo strtr($name, '\\', '-') ?>">
<?php print( \Markdown(strtr($properties['description'], array('*\\/' => '*/'))) ); print('<br>'); ?>
<ul>
<?php foreach ($properties['variables'] as $varName => $varProperties) { ?>
<li><?php echo $varName ?></li>
<?php } ?>
<?php foreach ($properties['methods'] as $methodName => $methodProperties) { ?>
<li id="class-<?php echo strtr($name, '\\', '-') ?>-method-<?php echo $methodName ?>"><a href="#" onclick="jQuery('#inner-' + this.parentNode.id).toggle(); return false;"><?php echo $methodName ?>(<?php $sep = ''; foreach ($methodProperties['arguments'] as $argName => $argProperties) {
    echo $sep . $argName; $sep = ', ';
}
?>)</a><br><?php echo $methodProperties['summary'] ?>
<div style="display: none" id="inner-class-<?php echo strtr($name, '\\', '-') ?>-method-<?php echo $methodName ?>">
<?php
        if ($methodProperties['description']) {
            print( \Markdown(strtr($methodProperties['description'], array('*\\/' => '*/'))) );
        }
        if (isset($methodProperties['arguments']) && count($methodProperties['arguments'])) {
            print('Arguments:<br>');
            print('<ul>');
            foreach($methodProperties['arguments'] as $argName => $argProperties) {
                if (isset($argProperties['phpdoc'])) {
                    printf('<li>%s: %s</li>', $argName, $argProperties['phpdoc']);
                } else {
                    printf('<li>%s</li>', $argName);
                }
            }
            print('</ul><br>');
        }
        if ($methodProperties['return']) {
            printf('Returns %s. <br><br>', $methodProperties['return']);
        }
?>
</div>
</li>
<?php } ?>
</ul>
</div>
</li>
<?php } ?>
</ul>
<?php

        return ob_get_clean();
    }/*}}}*/
    public function getHooksDoc() {/*{{{*/
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/phpdoc.php');
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/markdown.php');
        $phpDocParser = new \com\sergiosgc\phpdoc\PhpParser();

        $pluginFiles = $this->getAllSourceFiles();
        $pluginFiles = \ZeroMass::getInstance()->do_callback('com.sergiosgc.zeromass.plugin.phpdoc.getallSourceFiles', $pluginFiles);
        $phpDoc = array();
        foreach ($pluginFiles as $file) {
            $phpDoc = $phpDocParser->parseFile($file, $phpDoc);
        }
        if (!isset($phpDoc['hooks'])) $phpDoc['hooks'] = array();
        if (!isset($phpDoc['classes'])) $phpDoc['classes'] = array();
        if (!isset($phpDoc['namespaces'])) $phpDoc['namespaces'] = array();
        ob_start();
?>
<ul class="classList">
<?php foreach ($phpDoc['hooks'] as $name => $properties) { ?>
<li id="hook-<?php echo strtr($name, array('\\' => '-', '.' => '-', '*' => 'star')) ?>">
 <a href="#" onclick="jQuery('#inner-' + this.parentNode.id).toggle(); return false;"><?php echo $name ?></a>
<?php if ($properties['phpdoc']['summary'] ) print('<br>'); print( $properties['phpdoc']['summary'] ); ?>
 <div style="display: none" id="inner-hook-<?php echo strtr($name, array('\\' => '-', '.' => '-', '*' => 'star')) ?>">
<?php 
        if ($properties['phpdoc']['description']) {
            print( \Markdown(strtr($properties['phpdoc']['description'], array('*\\/' => '*/'))) );
        }
        if ($properties['phpdoc']['params']) {
            print('<h3>Arguments</h3> <ul>');
            foreach($properties['phpdoc']['params'] as $param) printf('<li>%s</li>', $param);
            print('</ul>');
        }
        if ($properties['phpdoc']['return']) {
            printf('<h3>Returns</h3> %s', $properties['phpdoc']['return']);
        }
        printf("<h3>Capture code</h3><pre><code>\\Pwf::getInstance()->register_callback(\n    '%s',\n    array(\$this, '%s')\n);</code></pre>", $name, preg_replace('_.*\.([a-zA-Z0-9]*).*_', '\1', $name));
        $argList = '';

?>
 </div>
 
</li>
<?php } ?>
</ul>
<?php

        return ob_get_clean();
    }/*}}}*/
    public function getDependencies() {/*{{{*/
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/markdown.php');
        if (!isset($this->usageSummary)) $this->parseSource();
        if (!isset($this->docBlockTagValues['depends'])) return array();
        if (!is_array($this->docBlockTagValues['depends'])) return array($this->docBlockTagValues['depends']);
        return $this->docBlockTagValues['depends'];
    }/*}}}*/
    public function install($destinationDir = null) {/*{{{*/
        $sourceDir = dirname($this->sourceFile);
        if (substr($sourceDir, -strlen($this->getId())) == $this->getId()) $sourceDir = dirname($sourceDir);
        if (is_null($destinationDir)) $destinationDir = \ZeroMass::getInstance()->pluginDir;
        $files = array();
        foreach ($this->getAllFiles() as $file) {
            $files[] = substr($file, strlen($sourceDir . DIRECTORY_SEPARATOR));
        }
        foreach ($files as $file) {
            $this->copyFile($file, $sourceDir, $destinationDir);
        }

        $installedSourceFile = strtr($this->sourceFile, array($sourceDir => $destinationDir));
        require_once($installedSourceFile);
        /*#
         * Allow just-installed plugin to react to physical file installation
         *
         * @param string Plugin ID of plugin just copied to filesystem
         */
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.pluginManager.install', $this->getId());
        /*#
         * Allow just-installed plugin to react to physical file installation
         */
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.pluginManager.install_' . $this->getId());
    }/*}}}*/
    protected function copyFile($file, $srcDir, $dstDir) {/*{{{*/
        if (!is_writable($dstDir)) throw new \ZeroMassException('Directory is not writable: ' . $dstDir);
        $file = explode(DIRECTORY_SEPARATOR, $file);
        if (count($file) == 1) {
            copy($srcDir . DIRECTORY_SEPARATOR . $file[0], $dstDir . DIRECTORY_SEPARATOR . $file[0]);
            chmod($dstDir . DIRECTORY_SEPARATOR . $file[0], fileperms($srcDir . DIRECTORY_SEPARATOR . $file[0]));
        } else {
            $dir = array_shift($file);
            $file = implode(DIRECTORY_SEPARATOR, $file);
            if (!is_dir($dstDir . DIRECTORY_SEPARATOR . $dir)) mkdir($dstDir . DIRECTORY_SEPARATOR . $dir);
            chmod($dstDir . DIRECTORY_SEPARATOR . $dir, fileperms($srcDir . DIRECTORY_SEPARATOR . $dir));
            $this->copyFile($file, $srcDir . DIRECTORY_SEPARATOR . $dir, $dstDir . DIRECTORY_SEPARATOR . $dir);
        }
    }/*}}}*/
}
?>
