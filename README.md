# ZeroMass Plugins

**This code is under development, and not usable in its current form.** The PluginManager works, but only with local repositories. Everything else is still far from ready. When ready, it should ammount to an Aspect-Oriented plugin stack for web application development.

This is a plugin repository for use with [ZeroMass](https://github.com/sergiosgc/ZeroMass). If you don't know what is ZeroMass, you are better off reading the documentation [there](https://github.com/sergiosgc/ZeroMass). If you do know and have installed ZeroMass, your next step for using this repository is installing the _ZeroMass Plugin Manager_ plugin. It will allow you to easily install plugins from this repository (and others)

## Installing the Plugin Manager

The Plugin Manager allows you to automate installation of plugins. In a true chicken and egg problem, the Plugin Manager itself must be installed by hand.

First, you need a working [installation of ZeroMass](https://github.com/sergiosgc/ZeroMass#webserver-configuration).

Then, clone this repository, and copy the Plugin Manager files and its dependencies to your ZeroMass plugin directory. **Do not** copy the whole repository there, as the other plugins may require extra installation steps.

The list of files to be copied over is:

    com.sergiosgc.pluginManager/
    com.sergiosgc.pluginManager/lib/
    com.sergiosgc.pluginManager/lib/markdown.php
    com.sergiosgc.pluginManager/lib/phpdoc.php
    com.sergiosgc.pluginManager/com.sergiosgc.plugin.php
    com.sergiosgc.pluginManager/css/
    com.sergiosgc.pluginManager/css/manager.css
    com.sergiosgc.pluginManager/com.sergiosgc.repository.php
    com.sergiosgc.pluginManager/img/
    com.sergiosgc.pluginManager/img/logo.png
    com.sergiosgc.pluginManager/com.sergiosgc.cache.php
    com.sergiosgc.pluginManager/com.sergiosgc.pluginManagerPage.php
    com.sergiosgc.pluginManager/com.sergiosgc.nosql.php
    com.sergiosgc.pluginManager/repository/
    com.sergiosgc.pluginManager/repository/file.php
    com.sergiosgc.pluginManager/com.sergiosgc.pluginManager.php
    com.sergiosgc.pluginManager/com.sergiosgc.pluginInstaller.php
    com.sergiosgc.ui.table.php
    com.sergiosgc.facility.php
    com.sergiosgc.switchboard.php

Which is basically:
- The `com.sergiosgc.pluginManager` directory and all its contents
- These single file plugins:
    - com.sergiosgc.ui.table
    - com.sergiosgc.facility
    - com.sergiosgc.switchboard

Then, access the PluginManager at [http://localhost/zeromass/plugins/](http://localhost/zeromass/plugins/), replacing `localhost` for the proper domain or IP if needed.
