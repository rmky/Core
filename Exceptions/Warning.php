<?php
namespace exface\Core\Exceptions;

use exface\Core\Interfaces\Exceptions\WarningExceptionInterface;
use exface\Core\Interfaces\Log\LoggerInterface;

/**
 * Exception thrown if a non-severe error occurs.
 * Using warnings enables code to distinguish between a critical error
 * condition, that should prevent further execution an non-critical errors, that do not endager the correct execution.
 *
 * For example, if a facade does not support certain widget attributes, the widget can still be drawn - probably not
 * exactly the way, the user intended, but still well useable.
 *
 * @author Andrej Kabachnik
 *        
 */
class Warning extends \Exception implements WarningExceptionInterface, \Throwable
{
    
    use ExceptionTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Exceptions\ExceptionInterface::getDefaultAlias()
     */
    public function getDefaultAlias()
    {
        return '6VCYFND';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Exceptions\ExceptionInterface::getDefaultLogLevel()
     */
    public function getDefaultLogLevel()
    {
        return LoggerInterface::ERROR;
    }
}