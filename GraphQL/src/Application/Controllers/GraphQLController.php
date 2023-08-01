<?php

namespace App\Application\Controllers;

use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class GraphQLController
{
    protected $db;
    protected $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->db = $connection;
        $this->logger = $logger;
    }

    private function setResolvers($resolvers)
{
    \GraphQL\Executor\Executor::setDefaultFieldResolver(function ($source, $args, $context, \GraphQL\Type\Definition\ResolveInfo $info) use ($resolvers) {
        $fieldName = $info->fieldName;

        if (is_null($fieldName)) {
            throw new \Exception('Could not get $fieldName from ResolveInfo');
        }

        if (is_null($info->parentType)) {
            throw new \Exception('Could not get $parentType from ResolveInfo');
        }

        $parentTypeName = $info->parentType->name;

        if (isset($resolvers[$parentTypeName])) {
            $resolver = $resolvers[$parentTypeName];

            if (is_array($resolver)) {
                if (array_key_exists($fieldName, $resolver)) {
                    $value = $resolver[$fieldName];

                    return is_callable($value) ? $value($source, $args, $context, $info) : $value;
                }
            }

            if (is_object($resolver)) {
                if (isset($resolver->{$fieldName})) {
                    $value = $resolver->{$fieldName};

                    return is_callable($value) ? $value($source, $args, $context, $info) : $value;
                }
            }
        }

        return \GraphQL\Executor\Executor::defaultFieldResolver($source, $args, $context, $info);
    });
}

    public function index(Request $request, Response $response) {

        $graphQLSyncPromiseAdapter = new SyncPromiseAdapter();
        $promiseAdapter = new WebonyxGraphQLSyncPromiseAdapter($graphQLSyncPromiseAdapter);

        # Injecting Connection for database access
        $dataLoaders = new DataLoaders($this->db);
        # Context, objects and data the resolver can then access. In this case the database object.
        $context = [            
            'loaders' => $dataLoaders->build($promiseAdapter),  
            'db'      => $this->db,            
            'logger'  => $this->logger        
        ];

        $this->setResolvers(include dirname(__DIR__, 3) . '/src/GraphQL/resolvers.php');

        $schema = \GraphQL\Utils\BuildSchema::build(file_get_contents(dirname(__DIR__, 3) . '/src/GraphQL/schema.graphqls'));

        $input = $request->getParsedBody();
$query = $input['query'];

$variables = isset($input['variables']) ? $input['variables'] : null;

$context = [
    'db'      => $this->db,
    'logger'  => $this->logger
];

$result = \GraphQL\GraphQL::executeQuery($schema, $query, null, $context, $variables);


    }

}