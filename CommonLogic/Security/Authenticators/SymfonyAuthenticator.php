<?php
namespace exface\Core\CommonLogic\Security\Authenticators;

use exface\Core\Interfaces\Security\AuthenticationTokenInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserChecker;
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
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use exface\Core\Exceptions\Security\AuthenticationFailedError;

class SymfonyAuthenticator extends AbstractAuthenticator
{
    private $authenticatedToken = null;
    
    private $authenticatedSymfonyToken = null;
    
    private $symfonyAuthManager = null;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Security\SecurityManagerInterface::authenticate()
     */
    public function authenticate(AuthenticationTokenInterface $token): AuthenticationTokenInterface
    {
        try {
            $symfonyToken = $this->createSymfonyAuthToken($token);
            $symfonyAuthenticatedToken = $this->getSymfonyAuthManager()->authenticate($symfonyToken);
            $this->authenticatedToken = $token;
            $this->authenticatedSymfonyToken = $symfonyAuthenticatedToken;
        } catch (AuthenticationException $e) {
            throw new AuthenticationFailedError($this, $e->getMessage(), null, $e);
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
     * @see \exface\Core\CommonLogic\Security\Authenticators\AbstractAuthenticator::getNameDefault()
     */
    protected function getNameDefault() : string
    {
        return 'Symfony Authentication';
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
            $this->getSymfonyDaoAuthenticationProvider()
        ];
    }
    
    protected function getSymfonyDaoAuthenticationProvider() : DaoAuthenticationProvider
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
                'secured_area'
                    );
            case $token instanceof PreAuthenticatedTokenInterface:
                return new PreAuthenticatedToken(
                $token->getUsername(),
                '',
                'secured_area'
                    );
        }
        return new AnonymousToken(
            'secret', new SymfonyUserWrapper(UserFactory::createAnonymous($this->getWorkbench()))
            );
    }
}