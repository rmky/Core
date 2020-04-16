<?php
namespace exface\Core\Interfaces\Model;

use exface\Core\Interfaces\WorkbenchDependantInterface;
use exface\Core\Interfaces\AliasInterface;
use exface\Core\Interfaces\Selectors\UiPageSelectorInterface;

/**
 * Common interface for anything, that can be put into a UI menu - pages, page tree nodes, etc.
 * 
 * @author Andrej Kabachnik
 *
 */
interface UiMenuItemInterface extends WorkbenchDependantInterface, AliasInterface
{    
    /**
     * 
     * @return bool
     */
    public function hasParent() : bool;

    /**
     * Returns the alias of the parent page (the actual parent - not a page, that replaces the parent!!!).
     * 
     * @return string
     */
    public function getParentPageSelector() : ?UiPageSelectorInterface;

    /**
     * Returns the unique id of the page.
     * 
     * This id is unique across all apps!
     * 
     * @return string|NULL
     */
    public function getUid() : ?string;

    /**
     * Returns the name of the page.
     * 
     * The name is what most facades will show as header and menu title.
     * 
     * @return string
     */
    public function getName() : string;
    
    /**
     * Returns the description of this page.
     * 
     * The description is used as hint, tooltip or similar by most facades.
     * It is a short text describing, what functionality the page offers:
     * e.g. "View an manage meta object of installed apps" for the object-page
     * in the metamodel editor.
     * 
     * @return string|NULL
     */
    public function getDescription() : ?string;
    
    /**
     * Overwrites the description of this page.
     *
     * The description is used as hint, tooltip or similar by most facades.
     * It is a short text describing, what functionality the page offers:
     * e.g. "View an manage meta object of installed apps" for the object-page
     * in the metamodel editor.
     *
     * @return string
     * @return UiMenuItemInterface
     */
    public function setDescription(string $string) : UiMenuItemInterface;

    /**
     * Returns an introduction text for the page to be used in contextual help, etc.
     * 
     * @return string|NULL
     */
    public function getIntro() : ?string;

    /**
     * Overwrites introduction text for the page.
     * 
     * @param string $string
     * @return UiMenuItemInterface
     */
    public function setIntro(string $text) : UiMenuItemInterface;
    
    /**
     * 
     * @param bool $true_or_false
     * @return UiMenuItemInterface
     */
    public function setPublished(bool $true_or_false) : UiMenuItemInterface;
    
    /**
     * 
     * @return bool
     */
    public function isPublished() : bool;
}