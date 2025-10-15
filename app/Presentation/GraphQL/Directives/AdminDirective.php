<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Directives;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Auth\Access\AuthorizationException;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class AdminDirective extends BaseDirective implements FieldMiddleware
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
"""
Requires the user to be authenticated and have admin role.
"""
directive @admin on FIELD_DEFINITION
GRAPHQL;
    }

    public function handleField(FieldValue $fieldValue): void
    {
        $fieldValue->wrapResolver(function (callable $resolver) {
            return function ($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) use ($resolver) {
                $user = $context->user();

                // Check if user is authenticated
                if ($user === null) {
                    throw new AuthorizationException('Unauthenticated.');
                }

                // Check if user is admin
                if (!method_exists($user, 'isAdmin') || !$user->isAdmin()) {
                    throw new AuthorizationException('This action requires admin privileges.');
                }

                // Call the actual resolver
                return $resolver($root, $args, $context, $resolveInfo);
            };
        });
    }
}
