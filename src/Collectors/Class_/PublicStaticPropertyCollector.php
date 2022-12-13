<?php

declare(strict_types=1);

namespace TomasVotruba\UnusedPublic\Collector\Class_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use TomasVotruba\UnusedPublic\PhpDoc\ApiDocStmtAnalyzer;

/**
 * @implements Collector<Class_, array<array{class-string, string, int}>>
 */
final class PublicStaticPropertyCollector implements Collector
{
    public function __construct(
        private ApiDocStmtAnalyzer $apiDocStmtAnalyzer
    ) {
    }

    /**
     * @return class-string<Node>
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @return array<array{string, string, int}>|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if (! $classReflection instanceof ClassReflection) {
            return null;
        }

        if ($this->apiDocStmtAnalyzer->isApiDoc($node, $classReflection)) {
            return null;
        }

        $classLike = $node->getOriginalNode();
        if (! $classLike instanceof Class_) {
            return null;
        }

        $staticPropertyNames = [];
        foreach ($classLike->getProperties() as $property) {
            if (! $property->isPublic()) {
                continue;
            }

            if (! $property->isStatic()) {
                continue;
            }

            foreach ($property->props as $propertyProperty) {
                $staticPropertyNames[] = [
                    $classReflection->getName(),
                    $propertyProperty->name->toString(),
                    $node->getLine(),
                ];
            }
        }

        return $staticPropertyNames;
    }
}
