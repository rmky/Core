<?php
namespace exface\Core\CommonLogic\Model;

use exface\Core\Interfaces\Model\UiPageInterface;
use exface\Core\Interfaces\Selectors\UiPageSelectorInterface;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Factories\UiPageFactory;
use exface\Core\Exceptions\InvalidArgumentException;
use exface\Core\Interfaces\WorkbenchInterface;
use exface\Core\Factories\SelectorFactory;

class UiPageTreeNode
{
    private $exface = null;
    
    private $tree = null;
    
    private $name = null;
    
    private $parentNode = null;
    
    private $pageSelector = null;
    
    private $childNodes = [];
    
    private $description = null;
    
    private $intro = null;
    
    private $cmsId = null;
    
    private $pageAlias = null;    
    
    
    public function __construct(WorkbenchInterface $exface, string $pageAlias, string $name, string $cmsId, UiPageTreeNode $parentNode = null)
    {
        $this->exface = $exface;
        $this->pageSelector = SelectorFactory::createPageSelector($exface, $pageAlias);
        $this->pageAlias = $pageAlias;
        $this->name = $name;
        $this->cmsId = $cmsId;
        if ($parentNode !== null) {
            $this->parentNode = $parentNode;
        }
        
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\WorkbenchDependantInterface::getWorkbench()
     */
    public function getWorkbench()
    {
        return $this->exface;
    }
    
    /**
     * 
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }
    
    /**
     * 
     * @return string
     */
    public function getPageAlias() : string
    {
        return $this->pageAlias;
    }
    
    /**
     * 
     * @return string
     */
    public function getCmsId() : string
    {
        return $this->cmsId;   
    }
    
    /**
     * 
     * @param UiPageTreeNode $parentNode
     * @return UiPageTreeNode
     */
    public function setParentNode(UiPageTreeNode $parentNode) : UiPageTreeNode
    {
        $this->parentNode = $parentNode;
        return $this;
    }
    
    /**
     * 
     * @return bool
     */
    public function hasParentNode() : bool
    {
        return $this->parentNode !== null;
    }
    
    /**
     * 
     * @return UiPageTreeNode|NULL
     */
    public function getParentNode() : ?UiPageTreeNode
    {
        return $this->parentNode;   
    }
    
    /**
     * Returns the nodes inherent page.
     * 
     * @return UiPageInterface
     */
    public function getPage() : UiPageInterface
    {
        return UiPageFactory::create($this->getPageSelector());
    }
    
    /**
     * 
     * @return UiPageSelectorInterface
     */
    public function getPageSelector() : UiPageSelectorInterface
    {
        return $this->pageSelector;
    }
    
    /**
     * 
     * @param string $intro
     * @return UiPageTreeNode
     */
    public function setIntro (string $intro) : UiPageTreeNode
    {
        $this->intro = $intro;
        return $this;
    }
    
    /**
     * 
     * @return bool
     */
    public function hasIntro() : bool
    {
        return $this->intro !== null;
    }
    
    /**
     * 
     * @return string
     */
    public function getIntro() : string
    {
        if ($this->intro !== null) {
            return $this->intro;
        }
        return '';
    }
    
    /**
     * 
     * @param string $descpription
     * @return UiPageTreeNode
     */
    public function setDescription (string $descpription) : UiPageTreeNode
    {
        $this->description = $descpription;
        return $this;
    }
    
    /**
     * 
     * @return string
     */
    public function getDescription() : string
    {
        if ($this->description !== null) {
            return $this->description;
        }
        return '';
    }
    
    /**
     * 
     * @return bool
     */
    public function hasChildNodes() : bool
    {
        return empty($this->childNodes) === false;
    }
    
    /**
     * 
     * @return array
     */
    public function getChildNodes() : array
    {
        return $this->childNodes;
    }
    
    /**
     * 
     * @param UiPageTreeNode $node
     * @param int $position
     * @throws InvalidArgumentException
     * @return UiPageTreeNode
     */
    public function addChildNode(UiPageTreeNode $node, int $position = null) : UiPageTreeNode
    {
        if ($node->getParentNode() !== $this){
            throw new InvalidArgumentException("The parent node of the given node '{$node->getName()}' is not the node '{$this->getName()}' !");
        }
        
        if ($position === null || ! is_numeric($position)) {
            $this->childNodes[] = $node;
        } else {
            array_splice($this->childNodes, $position, 0, [$node]);
        }
        
        return $this;
    }
    
    /**
     * Checks if the given page is equal to the page inherent to this node.
     * 
     * @param UiPageInterface $page
     * @return bool
     */
    public function isPage(UiPageInterface $page) : bool
    {
        return $page->is($this->getPageSelector());
    }
    
    /**
     * Checks if the page inherent to this node is the parent of the given page.
     * Returns `true` if it is.
     * 
     * @param UiPageInterface $page
     * @return bool
     */
    public function isParentOf(UiPageInterface $page) : bool
    {
        if ($page->getMenuParentPage() === null) {
            return false;
        }
        return $page->getMenuParentPage()->is($this->getPageSelector());        
    }
    
    /**
     * Checks if the page inherent to this node is an ancestor of the given page.
     * Returns `true` if it is.
     *
     * @param UiPageInterface $page
     * @return bool
     */
    public function isAncestorOf(UiPageInterface $page) : bool
    {
        $checkPage = $page;
        while ($checkPage->getMenuParentPage() !== null) {
            if ($this->isParentOf($checkPage)) {
                return true;
            }
            $checkPage = $checkPage->getMenuParentPage();            
        }
        return false;          
    }    
}