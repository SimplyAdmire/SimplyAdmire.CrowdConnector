[![Code Climate](https://codeclimate.com/github/SimplyAdmire/SimplyAdmire.CrowdConnector/badges/gpa.svg)](https://codeclimate.com/github/SimplyAdmire/SimplyAdmire.CrowdConnector)
[![Build Status](https://api.travis-ci.org/SimplyAdmire/SimplyAdmire.CrowdConnector.svg)](https://travis-ci.org/SimplyAdmire/SimplyAdmire.CrowdConnector)

# SimplyAdmire.CrowdConnector

The package offers an authentication provider that can be used to authenticate
users against Atlassian crowd. It comes with an importer that you could use
to keep the accounts in sync.

When a user is authenticated an account is created in the local database.

## Configuration
```yml
TYPO3:
  Flow:
    security:
      authentication:
        providers:
          crowdProvider:
            provider: SimplyAdmire\CrowdConnector\Provider\CrowdProvider
            providerOptions:
              instance: 'my.crowd.instance'
              
SimplyAdmire:
  CrowdConnector:
    instances:
      'my.crowd.instance':
        import:
          enabled: true
          createAccounts: true
          providerName: 'crowdProvider'
        roles:
          default:
            - 'My.Package:DefaultRole'
          mapping:
            'crowd-group-name':
              - 'My.Package:AdditionalRole'
              - 'My.Package:AdditionalRole2'
            'crowd-group-name2':
              - 'My.Package:AdditionalRole'
        url: 'https://my.crowd.domain.com/crowd/'
        applicationName: 'my-application-name'
        password: 'my-application-password'
        version: 1

```

## Import users
`./flow crowd:importusers`

The import will iterate over all configured instances. Users do not have to be
imported to be able to authenticate, when a non-existing user logs in an account
will automatically be created.

It has the following options:

* **import.enabled**:
    * not set or false: The instance is fully skipped by the importer.
    * true: Accounts found in the instance are imported. The minimum actions
        that are executed are: updating already existing accounts and disabling inactive accounts.
* **import.createAccounts**:
    * not set or false: The importer will not create account objects in the database.
    * true: An account objects in the database is created.
* **providerName**: The providername for imported accounts.

## Signals
The package contains a few signals to extend the mechanism for example to 
alter the list of roles, map a party object to an account or linking the account
to an existing party

### CrowdProvider
* **accountAuthenticated**: Signals after an account is authenticated.
    It receives 3 arguments:
    * *account*: The actual Account object
    * *userInformation*: Array with the crowd user information
    * *groupMembership*: Array with the groupmembership

### AccountService
* **accountCreated**: Signals after an accunt is created, it receives 2 arguments:
    * *account*: The actual Account object
    * *userInformation*: Array with the crowd user information
* **accountUpdated**: Signals after an accunt is updated, it receives 2 arguments:
    * *account*: The actual Account object
    * *userInformation*: Array with the crowd user information
* **accountActivated**: Signals after an accunt is activated, it receives a single argument:
    * *account*: The actual Account object
* **accountDeactivated**: Signals after an accunt is deactivated, it receives a single argument:
    * *account*: The actual Account object

---

For information on login boxes see: http://flowframework.readthedocs.org/en/latest/TheDefinitiveGuide/PartIII/Security.html#using-the-authentication-controller
