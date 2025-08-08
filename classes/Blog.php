<?php
require_once 'Database.php';

class Blog {
    private $db;
    private $lang;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->lang = Language::getInstance();
    }
    
    public function createPost($data) {
        try {
            $errors = $this->validatePostData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            $slug = $this->generateSlug($data['title']);
            if ($this->slugExists($slug, $data['language'])) {
                $slug = $this->generateUniqueSlug($slug, $data['language']);
            }
            
            $postData = [
                'title' => trim($data['title']),
                'slug' => $slug,
                'content' => $data['content'],
                'excerpt' => $this->generateExcerpt($data['content'], $data['excerpt'] ?? ''),
                'featured_image' => $data['featured_image'] ?? '',
                'author' => $data['author'] ?? 'PrestaCapi',
                'published' => isset($data['published']) ? (bool)$data['published'] : false,
                'language' => $data['language'] ?? $this->lang->getCurrentLanguage(),
                'meta_title' => $data['meta_title'] ?? $data['title'],
                'meta_description' => $data['meta_description'] ?? $this->generateExcerpt($data['content'], '', 160),
                'meta_keywords' => $data['meta_keywords'] ?? ''
            ];
            
            $postId = $this->db->insert('blog_posts', $postData);
            
            if ($postId) {
                $this->db->logActivity(null, null, 'blog_post_created', "Article de blog créé: {$postData['title']}");
                return [
                    'success' => true,
                    'message' => 'Article créé avec succès',
                    'post_id' => $postId,
                    'slug' => $slug
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création de l\'article'];
            
        } catch (Exception $e) {
            error_log("Erreur création article blog: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la création'];
        }
    }
    
    public function updatePost($postId, $data) {
        try {
            $existingPost = $this->getPostById($postId);
            if (!$existingPost) {
                return ['success' => false, 'message' => 'Article non trouvé'];
            }
            
            $errors = $this->validatePostData($data, $postId);
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            $updateData = [
                'title' => trim($data['title']),
                'content' => $data['content'],
                'excerpt' => $this->generateExcerpt($data['content'], $data['excerpt'] ?? ''),
                'featured_image' => $data['featured_image'] ?? $existingPost['featured_image'],
                'author' => $data['author'] ?? $existingPost['author'],
                'published' => isset($data['published']) ? (bool)$data['published'] : $existingPost['published'],
                'meta_title' => $data['meta_title'] ?? $data['title'],
                'meta_description' => $data['meta_description'] ?? $this->generateExcerpt($data['content'], '', 160),
                'meta_keywords' => $data['meta_keywords'] ?? $existingPost['meta_keywords']
            ];
            
            if ($data['title'] !== $existingPost['title']) {
                $newSlug = $this->generateSlug($data['title']);
                if ($this->slugExists($newSlug, $existingPost['language'], $postId)) {
                    $newSlug = $this->generateUniqueSlug($newSlug, $existingPost['language'], $postId);
                }
                $updateData['slug'] = $newSlug;
            }
            
            $this->db->update('blog_posts', $updateData, 'id = ?', [$postId]);
            $this->db->logActivity(null, null, 'blog_post_updated', "Article de blog mis à jour: {$updateData['title']}");
            
            return ['success' => true, 'message' => 'Article mis à jour avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur mise à jour article blog: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour'];
        }
    }
    
    public function deletePost($postId) {
        try {
            $post = $this->getPostById($postId);
            if (!$post) {
                return ['success' => false, 'message' => 'Article non trouvé'];
            }
            
            if (!empty($post['featured_image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $post['featured_image'])) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $post['featured_image']);
            }
            
            $this->db->delete('blog_posts', 'id = ?', [$postId]);
            $this->db->logActivity(null, null, 'blog_post_deleted', "Article de blog supprimé: {$post['title']}");
            
            return ['success' => true, 'message' => 'Article supprimé avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur suppression article blog: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la suppression'];
        }
    }
    
    public function getPostById($postId) {
        return $this->db->fetchOne("SELECT * FROM blog_posts WHERE id = ?", [$postId]);
    }
    
    public function getPostBySlug($slug, $language = null) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        $post = $this->db->fetchOne("
            SELECT * FROM blog_posts 
            WHERE slug = ? AND language = ? AND published = 1
        ", [$slug, $language]);
        
        if ($post) {
            $this->incrementViews($post['id']);
        }
        
        return $post;
    }
    
    public function getAllPosts($language = null, $published = true, $limit = null, $offset = 0) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        $sql = "SELECT * FROM blog_posts WHERE language = ?";
        $params = [$language];
        
        if ($published) {
            $sql .= " AND published = 1";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getFeaturedPosts($language = null, $limit = 3) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        return $this->db->fetchAll("
            SELECT * FROM blog_posts 
            WHERE language = ? AND published = 1 AND featured_image IS NOT NULL AND featured_image != ''
            ORDER BY views DESC, created_at DESC 
            LIMIT ?
        ", [$language, $limit]);
    }
    
    public function getRelatedPosts($currentPostId, $language = null, $limit = 3) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        return $this->db->fetchAll("
            SELECT * FROM blog_posts 
            WHERE id != ? AND language = ? AND published = 1
            ORDER BY RAND() 
            LIMIT ?
        ", [$currentPostId, $language, $limit]);
    }
    
    public function searchPosts($query, $language = null, $limit = 10) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        return $this->db->fetchAll("
            SELECT * FROM blog_posts 
            WHERE (title LIKE ? OR content LIKE ? OR excerpt LIKE ?) 
            AND language = ? AND published = 1
            ORDER BY created_at DESC 
            LIMIT ?
        ", ["%$query%", "%$query%", "%$query%", $language, $limit]);
    }
    
    public function getPostsByAuthor($author, $language = null, $limit = null) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        $sql = "SELECT * FROM blog_posts WHERE author = ? AND language = ? AND published = 1 ORDER BY created_at DESC";
        $params = [$author, $language];
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getArchiveByDate($year, $month = null, $language = null) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        $sql = "SELECT * FROM blog_posts WHERE YEAR(created_at) = ? AND language = ? AND published = 1";
        $params = [$year, $language];
        
        if ($month) {
            $sql .= " AND MONTH(created_at) = ?";
            $params[] = $month;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getBlogStats($language = null) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        $stats = [];
        
        $stats['total_posts'] = $this->db->count("SELECT COUNT(*) FROM blog_posts WHERE language = ?", [$language]);
        $stats['published_posts'] = $this->db->count("SELECT COUNT(*) FROM blog_posts WHERE language = ? AND published = 1", [$language]);
        $stats['draft_posts'] = $stats['total_posts'] - $stats['published_posts'];
        
        $totalViews = $this->db->fetchOne("SELECT SUM(views) as total FROM blog_posts WHERE language = ? AND published = 1", [$language]);
        $stats['total_views'] = $totalViews['total'] ?? 0;
        
        $avgViews = $this->db->fetchOne("SELECT AVG(views) as avg FROM blog_posts WHERE language = ? AND published = 1", [$language]);
        $stats['avg_views'] = round($avgViews['avg'] ?? 0);
        
        $mostPopular = $this->db->fetchOne("SELECT title, views FROM blog_posts WHERE language = ? AND published = 1 ORDER BY views DESC LIMIT 1", [$language]);
        $stats['most_popular'] = $mostPopular;
        
        $recentPosts = $this->db->fetchAll("SELECT title, created_at FROM blog_posts WHERE language = ? AND published = 1 ORDER BY created_at DESC LIMIT 5", [$language]);
        $stats['recent_posts'] = $recentPosts;
        
        return $stats;
    }
    
    public function generateSitemap($language = null) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        $posts = $this->db->fetchAll("
            SELECT slug, updated_at, created_at FROM blog_posts 
            WHERE language = ? AND published = 1 
            ORDER BY updated_at DESC
        ", [$language]);
        
        $urls = [];
        foreach ($posts as $post) {
            $urls[] = [
                'loc' => $this->lang->pageUrl('blog', $post['slug'], $language),
                'lastmod' => date('c', strtotime($post['updated_at'] ?: $post['created_at'])),
                'changefreq' => 'weekly',
                'priority' => '0.7'
            ];
        }
        
        return $urls;
    }
    
    public function uploadFeaturedImage($file, $postId = null) {
        try {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/blog/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5242880;
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
            }
            
            if ($file['size'] > $maxSize) {
                return ['success' => false, 'message' => 'Fichier trop volumineux (max 5MB)'];
            }
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                return ['success' => false, 'message' => 'Type de fichier non autorisé'];
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'blog_' . ($postId ?: 'new') . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            $webPath = '/uploads/blog/' . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement'];
            }
            
            $this->optimizeImage($targetPath);
            
            return [
                'success' => true,
                'message' => 'Image uploadée avec succès',
                'file_path' => $webPath,
                'file_name' => $filename
            ];
            
        } catch (Exception $e) {
            error_log("Erreur upload image blog: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
        }
    }
    
    private function validatePostData($data, $excludeId = null) {
        $errors = [];
        
        if (empty($data['title']) || strlen(trim($data['title'])) < 3) {
            $errors[] = 'Le titre est requis (minimum 3 caractères)';
        }
        
        if (empty($data['content']) || strlen(trim($data['content'])) < 50) {
            $errors[] = 'Le contenu est requis (minimum 50 caractères)';
        }
        
        if (!empty($data['language']) && !in_array($data['language'], $this->lang->getSupportedLanguages())) {
            $errors[] = 'Langue non supportée';
        }
        
        if (!empty($data['meta_title']) && strlen($data['meta_title']) > 60) {
            $errors[] = 'Le titre SEO ne doit pas dépasser 60 caractères';
        }
        
        if (!empty($data['meta_description']) && strlen($data['meta_description']) > 160) {
            $errors[] = 'La description SEO ne doit pas dépasser 160 caractères';
        }
        
        return $errors;
    }
    
    private function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        $accents = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE',
            'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I',
            'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
            'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
            'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i',
            'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y',
            'ÿ' => 'y'
        ];
        
        $slug = strtr($slug, $accents);
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return substr($slug, 0, 100);
    }
    
    private function slugExists($slug, $language, $excludeId = null) {
        $sql = "SELECT id FROM blog_posts WHERE slug = ? AND language = ?";
        $params = [$slug, $language];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return $this->db->count($sql, $params) > 0;
    }
    
    private function generateUniqueSlug($baseSlug, $language, $excludeId = null) {
        $counter = 1;
        $newSlug = $baseSlug;
        
        while ($this->slugExists($newSlug, $language, $excludeId)) {
            $newSlug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $newSlug;
    }
    
    private function generateExcerpt($content, $customExcerpt = '', $length = 200) {
        if (!empty($customExcerpt)) {
            return trim($customExcerpt);
        }
        
        $content = strip_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        if (strlen($content) <= $length) {
            return $content;
        }
        
        $excerpt = substr($content, 0, $length);
        $lastSpace = strrpos($excerpt, ' ');
        
        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }
        
        return $excerpt . '...';
    }
    
    private function incrementViews($postId) {
        $this->db->query("UPDATE blog_posts SET views = views + 1 WHERE id = ?", [$postId]);
    }
    
    private function optimizeImage($imagePath) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        $maxWidth = 1200;
        $maxHeight = 800;
        
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return true;
        }
        
        $sourceImage = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);
        
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $imagePath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $imagePath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $imagePath);
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return true;
    }
    
    public function getPostsByTag($tag, $language = null, $limit = 10) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        return $this->db->fetchAll("
            SELECT * FROM blog_posts 
            WHERE meta_keywords LIKE ? AND language = ? AND published = 1
            ORDER BY created_at DESC 
            LIMIT ?
        ", ["%$tag%", $language, $limit]);
    }
    
    public function getPopularTags($language = null, $limit = 20) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        $posts = $this->db->fetchAll("
            SELECT meta_keywords FROM blog_posts 
            WHERE language = ? AND published = 1 AND meta_keywords IS NOT NULL AND meta_keywords != ''
        ", [$language]);
        
        $tags = [];
        foreach ($posts as $post) {
            $postTags = array_map('trim', explode(',', $post['meta_keywords']));
            foreach ($postTags as $tag) {
                if (!empty($tag)) {
                    $tags[$tag] = ($tags[$tag] ?? 0) + 1;
                }
            }
        }
        
        arsort($tags);
        return array_slice($tags, 0, $limit, true);
    }
    
    public function getMonthlyArchive($language = null) {
        $language = $language ?: $this->lang->getCurrentLanguage();
        
        return $this->db->fetchAll("
            SELECT 
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                COUNT(*) as post_count,
                MONTHNAME(created_at) as month_name
            FROM blog_posts 
            WHERE language = ? AND published = 1
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY year DESC, month DESC
        ", [$language]);
    }
}