<?php

namespace AppBundle\Twig;

use AppBundle\AppBundle;
use AppBundle\Utils\Markdown;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\Intl\Intl;

class AppExtension extends \Twig_Extension
{
    private $locales;
    private $parser;
    private $doctrine;
    private $em;

    public function __construct($locales, Markdown $parser, ManagerRegistry $doctrine, EntityManagerInterface $em)
    {
        $this->locales = $locales;
        $this->parser = $parser;
        $this->doctrine = $doctrine;
        $this->em = $em;
    }

    public function getFunctions()
    {
        return [
                new \Twig_SimpleFunction('locales', [$this, 'getLocales']),
                new \Twig_SimpleFunction('has_content', [$this, 'userHasPublishedContent']),
                new \Twig_SimpleFunction('has_posts', [$this, 'categoryHasPosts']),
                new \Twig_SimpleFunction('has_courses', [$this, 'courseHasPosts']),
                new \Twig_SimpleFunction('course_slug', [$this, 'courseSlug']),
                new \Twig_SimpleFunction('course_name', [$this, 'courseName']),
                new \Twig_SimpleFunction('course_previous', [$this, 'coursePreviousArticle']),
                new \Twig_SimpleFunction('course_next', [$this, 'courseNextArticle']),
        ];
    }

    public function getFilters()
    {
        return array(
                new \Twig_SimpleFilter(
                        'md2html',
                        array($this, 'markdownToHtml'),
                        array('is_safe' => array('html'), 'pre_escape' => 'html')
                ),
        );
    }

    /**
     * Takes the list of codes of the locales (languages) enabled in the
     * application and returns an array with the name of each locale written
     * in its own language (e.g. English, Français, Español, etc.).
     */
    public function getLocales()
    {
        $localeCodes = explode('|', $this->locales);
        $locales = [];
        foreach ($localeCodes as $localeCode) {
            $locales[] = ['code' => $localeCode, 'name' => Intl::getLocaleBundle()->getLocaleName($localeCode, $localeCode)];
        }
        return $locales;
    }

    /*
     * Transforms the given Markdown content into HTML content.
     */
    public function markdownToHtml($content)
    {
        return $this->parser->toHtml($content);
    }

    /*
     * Checks if a given user id has (or not) published content
     */
    public function userHasPublishedContent($id)
    {
        $postRepo = $this->doctrine->getRepository('AppBundle:Post');
        $hasContent = $postRepo->findOneBy(array('author' => $id));
        if ($hasContent) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Checks if a given category id has (or not) published posts
     */
    public function categoryHasPosts($id)
    {
        $postRepo = $this->doctrine->getRepository('AppBundle:Post');
        $hasContent = $postRepo->findOneBy(array(
                'category' => $id,
                'status' => 'publish'
            )
        );
        if ($hasContent) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Checks if a given course id has (or not) published posts
     */
    public function courseHasPosts($id)
    {
        $postRepo = $this->doctrine->getRepository('AppBundle:Post');
        $hasContent = $postRepo->findOneBy(array(
                'course' => $id,
                'status' => 'publish'
            )
        );
        if ($hasContent) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Returns the name of the course in Spanish or English depends on current locale
     */
    public function courseName($id, $locale)
    {
        $courseRepo = $this->doctrine->getRepository('AppBundle:Course');
        $course = $courseRepo->findOneBy(array('id' => $id));
        if ($locale == 'en') {
            return $course->getNameEn();
        }
        return $course->getNameEs();
    }

    /*
     * Returns the slug of the course
     */
    public function courseSlug($id)
    {
        $courseRepo = $this->doctrine->getRepository('AppBundle:Course');
        $course = $courseRepo->findOneBy(array('id' => $id));
        return $course->getSlug();
    }

    /*
     * Returns the previous course's article required data or null if none exists
     */
    public function coursePreviousArticle($id, $course)
    {
        $dql = "SELECT p.titleEs, p.titleEn, p.slug 
                FROM AppBundle\Entity\Post p 
                WHERE p.course = :course AND p.id < :id AND p.status = 'publish'
                ORDER BY p.date DESC";
        $query = $this->em->createQuery($dql)
            ->setParameter('course', $course)
            ->setParameter('id', $id)
            ->setMaxResults(1);
        $article = $query->getOneOrNullResult();
        return $article;    }

    /*
     * Returns the next course's article required data or null if none exists
     */
    public function courseNextArticle($id, $course)
    {
        $dql = "SELECT p.titleEs, p.titleEn, p.slug 
                FROM AppBundle\Entity\Post p 
                WHERE p.course = :course AND p.id > :id AND p.status = 'publish'
                ORDER BY p.date ASC";
        $query = $this->em->createQuery($dql)
            ->setParameter('course', $course)
            ->setParameter('id', $id)
            ->setMaxResults(1);
        $article = $query->getOneOrNullResult();
        return $article;
    }
}
