<?php
namespace exface\Core\CommonLogic\Security\Authenticators;

use exface\Core\Interfaces\Security\AuthenticationTokenInterface;
use exface\Core\Interfaces\Security\SecurityManagerInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserChecker;
use exface\Core\Exceptions\RuntimeException;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use exface\Core\CommonLogic\Security\Symfony\SymfonyUserProvider;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use exface\Core\CommonLogic\Security\Symfony\SymfonyUserWrapper;
use exface\Core\CommonLogic\Security\Symfony\SymfonyNativePasswordEncoder;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use exface\Core\Factories\UserFactory;
use exface\Core\Interfaces\Security\PasswordAuthenticationTokenInterface;
use exface\Core\Interfaces\Security\PreAuthenticatedTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use exface\Core\Interfaces\Security\AuthenticatorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use exface\Core\Exceptions\Security\AuthenticationFailedError;
use exface\Core\Interfaces\WorkbenchInterface;

class SymfonyAuthenticator implements AuthenticatorInterface
{
    private $authenticatedToken = null;
    
    private $symfonyAuthManager = null;
    
    private $workbench = null;
    
    /**
     * 
     * @param WorkbenchInterface $workbench
     */
    public function __construct(WorkbenchInterface $workbench)
    {
        $this->workbench = $workbench;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Security\SecurityManagerInterface::authenticate()
     */
    public function authenticate(AuthenticationTokenInterface $token): AuthenticationTokenInterface
    {
        try {
            $this->getSymfonyAuthManager()->authenticate($this->createSymfonyAuthToken($token));
            $this->storeAuthenticatedToken($token);
        } catch (AuthenticationException $e) {
            throw new AuthenticationFailedError($e->getMessage(), null, $e);
        }
        return $token;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Security\SecurityManagerInterface::isAuthenticated()
     */
    public function isAuthenticated(AuthenticationTokenInterface $token) : bool
    {
        return $this->authenticatedToken === $token;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Security\AuthenticatorInterface::getName()
     */
    public function getName() : string
    {
        return 'Default Authentication';
    }
    
    protected function storeAuthenticatedToken(AuthenticationTokenInterface $token) : SecurityManagerInterface
    {
        if ($token->getUsername() !== $this->getAuthenticatedToken()->getUsername() && $this->getAuthenticatedToken()->isAnonymous() === false) {
            throw new RuntimeException('User changed!');
        }
        $this->authenticatedToken = $token;
        return $this;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\WorkbenchDependantInterface::getWorkbench()
     */
    public function getWorkbench()
    {
        return $this->workbench;
    }
    
    protected function getSymfonyAuthManager() : AuthenticationProviderManager
    {
        if ($this->symfonyAuthManager === null) {
            $this->symfonyAuthManager = new AuthenticationProviderManager($this->getSymfonyAuthProviders());
            if ($this->getWorkbench()->eventManager() instanceof EventDispatcherInterface) {
                $this->symfonyAuthManager->setEventDispatcher($this->getWorkbench()->eventManager()->getSymfonyEventDispatcher());
            }
        }
        return $this->symfonyAuthManager;
    }
    
    protected function getSymfonyAuthProviders() : array
    {
        return [
            $this->getSymfonyDaoAuthenticationProvier()
        ];
    }
    
    protected function getSymfonyDaoAuthenticationProvier() : DaoAuthenticationProvider
    {
        $userProvider = new SymfonyUserProvider($this->getWorkbench());
        $userChecker = new UserChecker();
        $encoderFactory = new EncoderFactory([
            SymfonyUserWrapper::class => (new SymfonyNativePasswordEncoder())
        ]);
        return new DaoAuthenticationProvider(
            $userProvider,
            $userChecker,
            'secured_area',
            $encoderFactory
            );
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Security\AuthenticatorInterface::isSupported()
     */
    public function isSupported(AuthenticationTokenInterface $token) : bool {
        return $token instanceof PasswordAuthenticationTokenInterface;
    }
    
    protected function createSymfonyAuthToken(AuthenticationTokenInterface $token)
    {
        switch (true) {
            case $token instanceof PasswordAuthenticationTokenInterface:
                return new UsernamePasswordToken(
                $token->getUsername(),
                $token->getPassword(),
                'workbench'
                    );
            case $token instanceof PreAuthenticatedTokenInterface:
                return new PreAuthenticatedToken(
                $token->getUsername(),
                '',
                'workbench'
                    );
        }
        return new AnonymousToken(
            'secret', new SymfonyUserWrapper(UserFactory::createAnonymous($this->getWorkbench()))
            );
    }
}