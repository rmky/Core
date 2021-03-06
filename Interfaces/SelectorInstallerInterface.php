<?php
namespace exface\Core\Interfaces;

use exface\Core\Interfaces\Selectors\SelectorInterface;

/**
 * Installs meta model components, that have selectors (e.g. apps)
 * 
 * @author Andrej Kabachnik
 *
 */
interface SelectorInstallerInterface extends InstallerInterface
{
    /**
     * Returns the selector of the element to install
     *
     * @return SelectorInterface
     */
    public function getSelectorInstalling();
}
?>