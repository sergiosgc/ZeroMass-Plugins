<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\ui;
/*#
 * com.sergiosgc.ui.paginator
 *
 * com.sergiosgc.ui.Paginator is an user-interface building helper class
 * for creating paginators that allow users to access multi-page results
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2013, Sérgio Carvalho
 * @version 1.0
 */
class Paginator {

    public function __construct($pageCount) {/*{{{*/
        $this->pageCount = $pageCount;
        $this->currentPageRequestArgument = 'p';
        $this->size = 10;
        $this->nextText = __('Next');
        $this->previousText = __('Previous');
        $this->form = null;
    }/*}}}*/
    protected function getCurrentPage() {/*{{{*/
        $result =  (int) $_REQUEST[$this->currentPageRequestArgument];
        if ($result > $this->pageCount) $result = $this->pageCount;
        if ($result < 1) $result = 1;

        return $result;
    }/*}}}*/
    protected function outputLinkToPage($page) {
        if (is_null($this->form)) {
            $href = '';
            $separator = '?';
            foreach ($_GET as $key => $value) {
                if (is_array($value)) {
                    $values = $value;
                    foreach ($values as $value) {
                        $href .= sprintf('%s%s[]=%s', 
                            $separator, 
                            urlencode($key), 
                            urlencode($value));
                        $separator = '&';
                    }
                } else {
                    $href .= sprintf('%s%s=%s', 
                        $separator, 
                        urlencode($key), 
                        urlencode($value));
                    $separator = '&';
                }
            }
            $href .= sprintf('%s%s=%s', 
                $separator, 
                urlencode($this->currentPageRequestArgument), 
                $page);
            printf(' href="%s"', $href);
        } else {
            printf(' href="#" onclick="comSergiosgcUiPaginatorGotoPage(%d)', 
                $page);
        }
    }
    public function output() {/*{{{*/
        $remaining = $this->size - 1;
        if ($remaining % 2 == 0) {
            $first = $this->getCurrentPage() - $remaining / 2;
            $last = $this->getCurrentPage() + $remaining / 2;
        } else {
            $first = $this->getCurrentPage() - ($remaining + 1) / 2;
            $last = $this->getCurrentPage() + ($remaining - 1) / 2;
        }
        if ($first < 1) {
            $last += (1 - $first);
            $first += (1 - $first);
        }
        if ($last > $this->pageCount) {
            $first += $this->pageCount - $last;
            $last += $this->pageCount - $last;
        }
        if ($first < 1) {
            $first += (1 - $first);
        }
?>
<ul class="pagination">
<?php if ($this->getCurrentPage() > 1) { ?>
<li>
<a<?php $this->outputLinkToPage($this->getCurrentPage() - 1) ?>><?php echo $this->previousText ?></a>
</li>
<?php } else { ?>
    <li class="disabled"><a href="#"><?php echo $this->previousText ?></a></li>
<?php
        }
        for ($i=$first; $i <= $last; $i++) {
            if ($i == $this->getCurrentPage()) {
?>
<li class="active">
<a href="#"><?php echo $i ?></a>
</li>
<?php       } else { ?>
<li> 
<a<?php $this->outputLinkToPage($i) ?>><?php echo $i ?></a>
</li>

<?php
            }
        }
?>
<li>
<?php if ($this->getCurrentPage() < $this->pageCount) { ?>
<a<?php $this->outputLinkToPage($this->getCurrentPage() + 1) ?>><?php echo $this->nextText ?></a>
<?php } ?>
</li>
</ul>
<?php
    }/*}}}*/
}
?>
