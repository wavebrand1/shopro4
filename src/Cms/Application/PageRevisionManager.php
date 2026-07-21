<?php

declare(strict_types=1);

namespace App\Cms\Application;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageRevision;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRevisionRepository;
use App\Identity\Domain\Entity\AdminUser;
use App\Identity\Domain\Entity\Membership;
use Doctrine\ORM\EntityManagerInterface;

final class PageRevisionManager
{
    public function __construct(private readonly PageRevisionRepository $revisions, private readonly EntityManagerInterface $entityManager) {}
    public function snapshot(Page $page, ?AdminUser $author): PageRevision
    {
        $revision = new PageRevision($page, $this->revisions->nextVersion($page), $this->capture($page), $author?->getDisplayName());
        $this->revisions->save($revision); return $revision;
    }
    /** @return array<string,mixed> */
    public function capture(Page $p): array
    {
        return ['title'=>$p->getTitle(),'slug'=>$p->getSlug(),'content'=>$p->getContent(),'editorMode'=>$p->getEditorMode(),'builderData'=>$p->getBuilderData(),'builderCss'=>$p->getBuilderCss(),'published'=>$p->isPublished(),'publishAt'=>$p->getPublishAt()?->format(DATE_ATOM),'unpublishAt'=>$p->getUnpublishAt()?->format(DATE_ATOM),'caption'=>$p->getCaption(),'seoTitle'=>$p->getSeoTitle(),'description'=>$p->getDescription(),'keywords'=>$p->getKeywords(),'meta'=>$p->getMeta(),'javascript'=>$p->getJavascript(),'canonical'=>$p->getCanonical(),'access'=>$p->getAccess(),'follow'=>$p->isFollow(),'homePage'=>$p->isHomePage(),'errorPage'=>$p->isErrorPage(),'adminOnly'=>$p->isAdminOnly(),'loginPage'=>$p->isLoginPage(),'activationPage'=>$p->isActivationPage(),'accountPage'=>$p->isAccountPage(),'registrationPage'=>$p->isRegistrationPage(),'searchPage'=>$p->isSearchPage(),'sitemapPage'=>$p->isSitemapPage(),'profilePage'=>$p->isProfilePage(),'termsPage'=>$p->isTermsPage(),'memberships'=>array_values(array_filter(array_map(static fn(Membership $m)=>$m->getId(),$p->getMemberships()->toArray())))];
    }
    public function restore(Page $p, PageRevision $revision): void
    {
        if ($revision->getPage()->getId() !== $p->getId()) throw new \LogicException('revision.wrong_page');
        $d=$revision->getData();
        $p->setTitle((string)$d['title']);$p->setSlug((string)$d['slug']);$p->setContent((string)$d['content']);$p->setEditorMode((string)$d['editorMode']);$p->setBuilderData((string)$d['builderData']);$p->setBuilderCss((string)$d['builderCss']);$p->setPublished((bool)$d['published']);$p->setPublishAt(isset($d['publishAt'])&&$d['publishAt']!==null?new \DateTimeImmutable((string)$d['publishAt']):null);$p->setUnpublishAt(isset($d['unpublishAt'])&&$d['unpublishAt']!==null?new \DateTimeImmutable((string)$d['unpublishAt']):null);$p->setCaption((string)$d['caption']);$p->setSeoTitle((string)$d['seoTitle']);$p->setDescription((string)$d['description']);$p->setKeywords((string)$d['keywords']);$p->setMeta((string)$d['meta']);$p->setJavascript((string)$d['javascript']);$p->setCanonical((string)$d['canonical']);$p->setAccess((string)$d['access']);$p->setFollow((bool)$d['follow']);
        foreach(['HomePage','ErrorPage','AdminOnly','LoginPage','ActivationPage','AccountPage','RegistrationPage','SearchPage','SitemapPage','ProfilePage','TermsPage'] as $field){$key=lcfirst($field);$p->{'set'.$field}((bool)($d[$key]??false));}
        foreach($p->getMemberships()->toArray() as $membership)$p->removeMembership($membership);
        foreach((array)($d['memberships']??[]) as $id){$membership=$this->entityManager->find(Membership::class,(int)$id);if($membership)$p->addMembership($membership);}
    }
    /** @return list<string> */
    public function changes(PageRevision $current, ?PageRevision $previous): array
    {
        if (!$previous) return ['revision.initial']; $labels=[];$groups=['revision.content'=>['title','caption','content','editorMode','builderData','builderCss'],'revision.url'=>['slug','canonical'],'revision.seo'=>['seoTitle','description','keywords','meta','follow'],'revision.visibility'=>['published','publishAt','unpublishAt','access','memberships'],'revision.roles'=>['homePage','errorPage','adminOnly','loginPage','activationPage','accountPage','registrationPage','searchPage','sitemapPage','profilePage','termsPage']];
        foreach($groups as $label=>$fields)foreach($fields as $field)if(($current->getData()[$field]??null)!==($previous->getData()[$field]??null)){$labels[]=$label;break;}
        return $labels?:['revision.no_material_changes'];
    }
}
