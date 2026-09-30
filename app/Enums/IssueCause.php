<?php

namespace App\Enums;

enum IssueCause: string
{
    case InternalKnowledgeGap = 'internal_knowledge_gap';
    case CustomerKnowledgeGap = 'customer_knowledge_gap';
    case UserError = 'user_error';
    case CodeDefect = 'code_defect';
    case ConfigurationError = 'configuration_error';
    case Infrastructure = 'infrastructure';
    case ExternalDependency = 'external_dependency';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::InternalKnowledgeGap => 'Kennis ontbreekt intern',
            self::CustomerKnowledgeGap => 'Kennis ontbreekt bij klant',
            self::UserError => 'Gebruikersfout',
            self::CodeDefect => 'Codebug',
            self::ConfigurationError => 'Configuratiefout',
            self::Infrastructure => 'Infrastructuur',
            self::ExternalDependency => 'Externe afhankelijkheid',
            self::Other => 'Anders',
        };
    }
}
