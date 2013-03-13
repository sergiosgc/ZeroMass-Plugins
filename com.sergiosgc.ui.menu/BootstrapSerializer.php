<?php
namespace com\sergiosgc\ui\menu;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
class BootstrapSerializer
{
    protected $stacked = true;
    protected $list = false;
    protected $pills = false;
    protected $tabs = true;
    protected $brand = false;
    public function __construct() {
    }
    public function enableBrand($brand = true) {
        $this->brand = $brand;
        return $this;
    }
    public function setStacked($to = true) {
        $this->stacked = $to;
        return $this;
    }
    public function setPills($to = true) {
        $this->pills = $to;
        return $this;
    }
    public function setTabs($to = true) {
        $this->tabs = $to;
        return $this;
    }
    public function serialize(Menu $menu) {
        $this->serializeMenu($menu, true);
    }
    public function serializeItem(MenuItem $item) {
        if ($item instanceof Leaf) {
            $this->serializeLeaf($item);
        } else {
            $this->serializeMenu($item);
        }
    }
    protected function serializeMenu(Menu $menu, $topLevel = false) {
        $class = 'nav';
        if ($this->stacked) $class .= ' nav-stacked';
        if ($this->pills) $class .= ' nav-pills';
        if ($this->list) $class .= ' nav-list';
        if ($this->tabs) $class .= ' nav-tabs';
        $items = $menu->getItems();
        if ($topLevel) {
            if ($this->brand) {
                printf('<a href="%s" class="navbar-brand brand">%s</a>', $items[0]->getHref(), $items[0]->getLabel());
                array_shift($items);
            }
            printf("<ul class=\"%s\">\n", $class);
        } else {
            $liClass = $menu->getActive() ? 'active' : '';
            //printf('<li class="%s"><ul class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="%s">%s<b class="caret"></b></a><ul class="dropdown-menu">%s',
            printf('<li class="%s dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="%s">%s<b class="caret"></b></a><ul class="dropdown-menu">%s',
                $liClass,
                $menu->getHref(),
                $menu->getLabel(),
                "\n");
        }
        foreach ($items as $item) {
            $this->serializeItem($item);
        }
        if ($topLevel) print("</ul>\n"); else print("</ul></li>\n");
    }
    protected function serializeLeaf(Leaf $leaf) {
        $class = $leaf->getActive() ? 'active' : '';
        printf('<li class="%s"><a href="%s">%s</a></li>%s', $class, $leaf->getHref(), $leaf->getLabel(), "\n");
    }
}
?>

