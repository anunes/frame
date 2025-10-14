<?php

namespace app\console;

class ListCommands
{
    /**
     * Handle the command
     */
    public function handle(array $arguments): void
    {
        echo "\033[32m";
        echo "  ___                       \n";
        echo " | __|_ _ __ _ _ __  ___    \n";
        echo " | _| '_/ _` | '  \\/ -_)   \n";
        echo " |_||_| \\__,_|_|_|_\\___|   \n";
        echo "\033[0m\n";
        echo "\033[33mFrame CLI Tool - v1.0.0\033[0m\n\n";

        echo "\033[36mUsage:\033[0m\n";
        echo "  php frame <command> [arguments]\n\n";

        echo "\033[36mAvailable Commands:\033[0m\n\n";

        echo "\033[33mGenerate:\033[0m\n";
        echo "  \033[32mmake:controller\033[0m <name>     Create a new controller class\n";
        echo "  \033[32mmake:model\033[0m <name>          Create a new model class\n";
        echo "  \033[32mmake:view\033[0m <name>           Create a new view template file\n";
        echo "  \033[32mmake:migration\033[0m <name>      Create a new database migration\n\n";

        echo "\033[33mDatabase:\033[0m\n";
        echo "  \033[32mmigrate\033[0m                    Run all pending database migrations\n";
        echo "  \033[32mmigrate:rollback\033[0m           Rollback the last batch of migrations\n\n";

        echo "\033[33mCache:\033[0m\n";
        echo "  \033[32mcache:clear\033[0m                Clear application cache (views, OPcache)\n\n";

        echo "\033[33mDevelopment:\033[0m\n";
        echo "  \033[32mserve\033[0m [port]              Start development server (default: 8000)\n\n";

        echo "\033[33mRouting:\033[0m\n";
        echo "  \033[32mroute:list\033[0m                 List all registered routes\n\n";

        echo "\033[33mOther:\033[0m\n";
        echo "  \033[32mlist\033[0m                       Show this help message\n\n";

        echo "\033[36mExamples:\033[0m\n";
        echo "  php frame make:controller ProductController\n";
        echo "  php frame make:model Product\n";
        echo "  php frame make:view admin/products\n";
        echo "  php frame make:migration create_products_table\n";
        echo "  php frame migrate\n";
        echo "  php frame route:list\n";
        echo "  php frame serve 8080\n\n";
    }
}
