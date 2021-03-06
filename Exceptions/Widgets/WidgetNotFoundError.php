<?php
namespace exface\Core\Exceptions\Widgets;

use exface\Core\Exceptions\RuntimeException;

/**
 * Exception thrown if a widget was unexpectedly not found in a page or a widget link is unresolvable.
 *
 * @author Andrej Kabachnik
 *        
 */
class WidgetNotFoundError extends RuntimeException
{

    public function getDefaultAlias()
    {
        return '6T91E4Q';
    }
}