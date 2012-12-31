<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\zeromass;
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'com.sergiosgc.plugin.php');

class PluginManagerPage {
    protected $title = '';
    protected $breadcrumbs = array();
    public function setTitle($title) /*{{{*/
    {
        $this->title = $title;
    }/*}}}*/
    public function start() {/*{{{*/
        ob_start(array($this, 'outputPage'));
    }/*}}}*/
    public function done() {/*{{{*/
        ob_end_flush();
    }/*}}}*/
    public function addBreadcrumb($label, $href) {/*{{{*/
        $this->breadcrumbs[] = array(
            'label' => $label,
            'href' => $href
        );
    }/*}}}*/
    public function outputPage($buffer) {/*{{{*/
        return sprintf(<<<EOS
<!DOCTYPE html>
<html>
 <head>
 <title>%s</title>
  <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap-combined.min.css" rel="stylesheet">
  <link href="/zeromass/plugins/com.sergiosgc.pluginManager/css/manager.css" rel="stylesheet">
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
  <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/js/bootstrap.min.js"></script>
  <style type="text/css">
@import url(http://fonts.googleapis.com/css?family=Allerta+Stencil);
.navbar {
 display: none;
}
.sidebar-nav .icon-chevron-right {
 float: right;
}
.sidebar-nav > .nav-list {
 background-color: #FFFFFF;
 border-radius: 6px 6px 6px 6px;
 box-shadow: 0 1px 4px rgba(0, 0, 0, 0.067);
 margin: 0;
 padding: 0;
}
.sidebar-nav > .nav-list > li.active > a {
 padding: 9px 15px;
 box-shadow: 1px 0 0 rgba(0, 0, 0, 0.1) inset, -1px 0 0 rgba(0, 0, 0, 0.1) inset;
}
.sidebar-nav > .nav-list > li > a {
 border: 1px solid #E5E5E5;
 display: block;
 margin: 0 0 -1px;
 padding: 8px 14px;
}
.navbar .brand, .navbar .nav > li > a.brand {
 background-image: url("/zeromass/plugins/com.sergiosgc.pluginManager/img/logo.png");
 background-position: 10px bottom;
 background-repeat: no-repeat;
 color: #666666;
 font-family: 'Allerta Stencil', sans-serif;
 padding-left: 46px;
 padding-top: 8px; 
}
  </style>
 </head>
 <body>
  <div class="navbar">
   <div class="navbar-inner">
    <a class="brand" href="#">
ZeroMass Plugin Manager</a>
    <ul class="nav">
     <li class="%s"><a href="#"><i class="icon-home icon-white"></i> Home</a></li>
     <li><a href="http://github.com/"><i class="icon-question-sign icon-white"></i> About</a></li>
    </ul>
   </div>
  </div>
  <div class="container-fluid">
   <div class="row-fluid">
    <div class="span3">
     <div class="sidebar-nav">
      <ul class="nav nav-list">
%s
      </ul>
     </div><!--/.well -->
    </div>
    <div class="span9">
%s
%s
    </div>
   </div>
  </div>
  <footer class="footer" style="background-color: #F5F5F5; border-top: 1px solid #E5E5E5; margin-top: 70px; padding: 70px 0">
   <div class="container">
    <p class="pull-right" style="color: #777777"><a href="#">Back to top</a></p>
    <p style="color: #777777">&copy; Copyright 2012, Sérgio Carvalho &lt;sergiosgc@gmail.com&gt;</p>
   </div>
  </footer>
 </body>
</html>
EOS
        , $this->title, $_SERVER['REQUEST_URI'] == '/zeromass/plugins/' ? 'active' : '', $this->generateMenu(), $this->generateBreadcrumbs(), $buffer);
    }/*}}}*/
    protected function generateMenu() {/*{{{*/
        $entries = array(
            array(
                'label' => 'List plugins',
                'href' => '/zeromass/plugins/',
                'active' => array(
                    '_^/zeromass/plugins/.*$_',
                    '_^/zeromass/plugin/.*$_'
                )
            ),
            array(
                'label' => 'Settings',
                'href' => '/zeromass/settings/',
                'active' => array(
                    '_^/zeromass/settings/.*$_',
                )
            ),
            array(
                'label' => 'Exit',
                'href' => '/',
                'active' => array(
                )
            ),
        );
        $result = '';
        foreach ($entries as $entry) {
            $active = false;
            foreach ($entry['active'] as $regex) {
                if (preg_match($regex, $_SERVER['REQUEST_URI'])) $active = true;
            }
            $result .= sprintf('<li%s><a href="%s"><i class="icon-chevron-right"></i>%s</a></li>' . "\n", $active ? ' class="active"' : '', $entry['href'], $entry['label']);
        }
        return $result;
    }/*}}}*/
    protected function generateBreadcrumbs() {/*{{{*/
        if (count($this->breadcrumbs) == 0) return '';
        $result = '<ul class="breadcrumb well">' . "\n";
        foreach ($this->breadcrumbs as $idx => $crumb) if ($idx == count($this->breadcrumbs) - 1) {
            $result .= sprintf('<li class="active">%s</li>' . "\n", $crumb['label']);
        } else {
            $result .= sprintf('<li><a href="%s">%s</a> <span class="divider">/</span></li>' . "\n", $crumb['href'], $crumb['label']);
        }
        $result .= '</ul>';
        return $result;
    }/*}}}*/
}
?>
