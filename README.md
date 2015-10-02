[![Code Climate](https://codeclimate.com/github/SimplyAdmire/SimplyAdmire.CrowdConnector/badges/gpa.svg)](https://codeclimate.com/github/SimplyAdmire/SimplyAdmire.CrowdConnector)

# SimplyAdmire.CrowdConnector

Use this package to import and authenticate your Atlassian Crowd users.

## What does this pacakge do?

When you use Crowd u can use this package to import your users via a cli command.
After that your users are imported and Flow accounts are created to use.

### 1. Set this in your Configuration/Settings.yaml
```yml
TYPO3:
  Flow:
    security:
      authentication:
        authenticationStrategy: atLeastOneToken
        providers:
          crowdProvider:
            provider: SimplyAdmire\CrowdConnector\Provider\CrowdProvider
            providerOptions:
              crowdServerUrl: 'https://your.crowd.url/crowd'
              password: 'your-api-password'
```

### 2. Import users
`./flow crowd:importusers`

After that accounts are created and Flow will be able to authenticate via your Crowd server.

---

For information on login boxes see: http://flowframework.readthedocs.org/en/latest/TheDefinitiveGuide/PartIII/Security.html#using-the-authentication-controller
