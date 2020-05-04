<?php
namespace exface\Core\CommonLogic\Security\Authorization;

use exface\Core\Interfaces\UserImpersonationInterface;
use exface\Core\DataTypes\PolicyEffectDataType;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Interfaces\Security\AuthorizationPointInterface;
use exface\Core\Factories\PermissionFactory;
use exface\Core\Interfaces\Facades\FacadeInterface;

/**
 * 
 * 
 * @method FacadeAuthorizationPolicy[] getPolicies()
 * 
 * @author Andrej Kabachnik
 *
 */
class FacadeAuthorizationPoint extends AbstractAuthorizationPoint
{

    /**
     * 
     * @see \exface\Core\Interfaces\Security\AuthorizationPointInterface::authorize()
     */
    public function authorize(FacadeInterface $facade = null, UserImpersonationInterface $userOrToken = null) : FacadeInterface
    {
        if ($this->isDisabled()) {
            return PermissionFactory::createPermitted();
        }
        
        if ($userOrToken === null) {
            $userOrToken = $this->getWorkbench()->getSecurity()->getAuthenticatedToken();
        }
        
        $permissionsGenerator = $this->evaluatePolicies($facade, $userOrToken);
        $this->combinePermissions($permissionsGenerator, $userOrToken, $facade);
        return $facade;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Security\AuthorizationPointInterface::addPolicy()
     */
    public function addPolicy(array $targets, PolicyEffectDataType $effect, string $name = '', UxonObject $condition = null) : AuthorizationPointInterface
    {
        $this->addPolicyInstance(new FacadeAuthorizationPolicy($this->getWorkbench(), $name, $effect, $targets, $condition));
        return $this;
    }
    
    /**
     * 
     * @param FacadeInterface $facade
     * @param UserImpersonationInterface $userOrToken
     * @return \Generator
     */
    protected function evaluatePolicies(FacadeInterface $facade, UserImpersonationInterface $userOrToken) : \Generator
    {
        foreach ($this->getPolicies($userOrToken) as $policy) {
            yield $policy->authorize($userOrToken, $facade);
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\Security\Authorization\AbstractAuthorizationPoint::getResourceName()
     */
    protected function getResourceName($resource) : string
    {
        return "facade '{$resource->getAliasWithNamespace()}'";
    }
}