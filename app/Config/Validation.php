<?php

namespace Config;

use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends \CodeIgniter\Config\Validation
{
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    // ── AUTH ──
    public array $login = [
        'email'    => 'required|valid_email',
        'password' => 'required|min_length[8]',
    ];

    public array $refresh = [
        'refreshToken' => 'required|min_length[16]',
    ];

    // ── USERS ──
    public array $createUser = [
        'email'     => 'required|valid_email|is_unique[users.email]',
        'password'  => 'required|min_length[8]',
        'firstName' => 'permit_empty|min_length[2]|max_length[80]',
        'lastName'  => 'permit_empty|min_length[2]|max_length[80]',
        'username'  => 'permit_empty|min_length[3]|max_length[40]|regex_match[/^[a-zA-Z0-9._-]+$/]|is_unique[users.username]',
        'role'      => 'permit_empty|in_list[super_admin,editor,writer]',
        'active'    => 'permit_empty|in_list[0,1,true,false]',
    ];

    public array $updateUser = [
        'email'     => 'permit_empty|valid_email',
        'password'  => 'permit_empty|min_length[8]',
        'firstName' => 'permit_empty|min_length[2]|max_length[80]',
        'lastName'  => 'permit_empty|min_length[2]|max_length[80]',
        'username'  => 'permit_empty|min_length[3]|max_length[40]|regex_match[/^[a-zA-Z0-9._-]+$/]',
        'active'    => 'permit_empty|in_list[0,1,true,false]',
    ];

    // ── NEWS ──
    public array $createNews = [
        'title'       => 'required|min_length[5]',
        'summary'     => 'required|min_length[10]',
        'content'     => 'required|min_length[20]',
        'heroImage'   => 'permit_empty|valid_url_strict',
        'heroImageId' => 'permit_empty|max_length[36]',
        'authorId'    => 'permit_empty|max_length[36]',
        'status'      => 'required|in_list[draft,in_review,approved,scheduled,published]',
        'featured'    => 'permit_empty|in_list[0,1,true,false]',
    ];

    public array $updateNews = [
        'title'       => 'permit_empty|min_length[5]',
        'summary'     => 'permit_empty|min_length[10]',
        'content'     => 'permit_empty|min_length[20]',
        'heroImage'   => 'permit_empty|valid_url_strict',
        'heroImageId' => 'permit_empty|max_length[36]',
        'authorId'    => 'permit_empty|max_length[36]',
        'status'      => 'permit_empty|in_list[draft,in_review,approved,scheduled,published]',
        'featured'    => 'permit_empty|in_list[0,1,true,false]',
    ];

    public array $scheduleNews = [
        'publishAt' => 'required|valid_date[Y-m-d\TH:i:s]',
    ];

    // ── AUTHORS ──
    public array $createAuthor = [
        'displayName' => 'required|min_length[2]|max_length[120]',
        'bio'         => 'permit_empty|max_length[2000]',
        'avatar'      => 'permit_empty|valid_url_strict',
    ];

    // ── CATEGORIES ──
    public array $createCategory = [
        'name' => 'required|min_length[2]|max_length[100]|is_unique[categories.name]',
    ];

    // ── TAGS ──
    public array $createTag = [
        'name' => 'required|min_length[2]|max_length[100]|is_unique[tags.name]',
    ];

    // ── MEDIA / IMAGES ──
    public array $uploadImage = [
        'fileName'       => 'required|min_length[3]',
        'mimeType'       => 'required|in_list[image/jpeg,image/png,image/webp,image/gif]',
        'fileDataBase64' => 'required|min_length[20]',
        'title'          => 'permit_empty|max_length[255]',
        'alt'            => 'permit_empty|max_length[255]',
        'caption'        => 'permit_empty|max_length[500]',
    ];

    // ── ADS ──
    public array $createAd = [
        'name'      => 'required|min_length[2]|max_length[120]',
        'placement' => 'required|in_list[home_main,article_inline,sidebar,list_inline]',
        'type'      => 'required|in_list[internal,external]',
    ];

    // ── POLLS ──
    public array $createPoll = [
        'title'       => 'required|min_length[5]|max_length[255]',
        'description' => 'permit_empty|max_length[2000]',
        'status'      => 'permit_empty|in_list[draft,active,closed]',
    ];

    // ── NEWSLETTER ──
    public array $subscribe = [
        'email'  => 'required|valid_email',
        'source' => 'permit_empty|max_length[100]',
    ];

    // ── SETTINGS ──
    public array $userSettings = [
        'theme' => 'permit_empty|in_list[system,light,dark,sepia]',
    ];
}
