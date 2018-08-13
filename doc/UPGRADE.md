Upgrade instructions
====================

Upgrade to 1.5.0
----------------

Release 1.5.0 comes with Symfony based admin interface for managing collected information.
In order to work properly, routing file must be included in main routing configuration file:

```yaml
_netgen_information_collection:
    resource: '@NetgenInformationCollectionBundle/Resources/config/routing.yml'
``` 