<?php

declare(strict_types=1);

namespace App\Module\Presentation\Twig;

use App\Module\Application\AdminModuleDefinition;
use App\Module\Application\ModuleRegistry;
use App\Module\Application\ModuleRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ModuleExtension extends AbstractExtension
{
    public function __construct(private readonly ModuleRuntime $runtime,private readonly ModuleRegistry $registry) {}
    public function getFunctions(): array { return [new TwigFunction('shopro_module_enabled', $this->runtime->isEnabled(...)),new TwigFunction('shopro_admin_modules',$this->adminModules(...))]; }
    /** @return list<array{code:string,name:string,route:string,routePrefix:string}> */
    public function adminModules():array
    {
        $items=[];foreach($this->registry->all() as $module)if($module instanceof AdminModuleDefinition&&$this->runtime->isEnabled($module->code()))$items[]=['code'=>$module->code(),'name'=>$module->name(),'route'=>$module->adminRoute(),'routePrefix'=>$module->adminRoutePrefix()];return $items;
    }
}
