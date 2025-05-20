This example defineds a schema with entities `User` and `UserGroup` that reference each other via a join table. Using data loaders, this can be efficiently implemented.

The entities in src/Entity have a `resolve` parameter on the properties defining the Doctrine relations and use a data loader defined via the `overblog/dataloader-bundle` package.

These loaders are implemented in `src/GraphQL/Loader` and use an implementation of `Pander\DataLoaderSupport\LoaderInterface` to quickly define methods that describe how these relations should be loaded.
