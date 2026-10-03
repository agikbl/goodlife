<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();

function category_error($message, $status = 400)
{
    admin_json_response(['ok' => false, 'error' => $message], $status);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    category_error('Data kategori tidak valid.');
}

$action = $input['action'] ?? '';
try {
    if ($action === 'add') {
        $name = is_scalar($input['nama'] ?? null) ? trim((string)$input['nama']) : '';
        $group = $input['kelompok'] ?? '';
        if ($name === '' || strlen($name) > 60 || !in_array($group, ['makanan', 'minuman'], true)) {
            category_error('Nama kategori maksimal 60 karakter dan kelompok harus dipilih.');
        }
        $created = admin_mutate_json('data/categories.json', function (&$categories) use ($name, $group) {
            foreach ($categories as $category) {
                if (strcasecmp((string)($category['nama'] ?? ''), $name) === 0) {
                    throw new DomainException('Nama kategori sudah digunakan.');
                }
            }
            $category = [
                'id' => 'cat_' . bin2hex(random_bytes(6)),
                'nama' => $name,
                'kelompok' => $group,
            ];
            $categories[] = $category;
            return $category;
        });
        admin_json_response(['ok' => true, 'category' => $created]);
    }

    if ($action === 'update') {
        $id = is_scalar($input['id'] ?? null) ? trim((string)$input['id']) : '';
        $name = is_scalar($input['nama'] ?? null) ? trim((string)$input['nama']) : '';
        $group = $input['kelompok'] ?? '';
        if ($id === '' || $name === '' || strlen($name) > 60 ||
            !in_array($group, ['makanan', 'minuman'], true)) {
            category_error('ID, nama kategori, atau kelompok tidak valid.');
        }
        $updated = admin_mutate_json('data/categories.json', function (&$categories) use ($id, $name, $group) {
            foreach ($categories as $category) {
                if (($category['id'] ?? '') !== $id && strcasecmp((string)($category['nama'] ?? ''), $name) === 0) {
                    throw new DomainException('Nama kategori sudah digunakan.');
                }
            }
            foreach ($categories as &$category) {
                if (($category['id'] ?? '') === $id) {
                    $category['nama'] = $name;
                    $category['kelompok'] = $group;
                    $result = $category;
                    unset($category);
                    return $result;
                }
            }
            unset($category);
            throw new OutOfBoundsException('Kategori tidak ditemukan.');
        });
        admin_json_response(['ok' => true, 'category' => $updated]);
    }

    if ($action === 'delete') {
        $id = is_scalar($input['id'] ?? null) ? trim((string)$input['id']) : '';
        if ($id === '' || !preg_match('/^(?:[a-z0-9_]+|cat_[a-f0-9]{12})$/', $id)) {
            category_error('ID kategori tidak valid.');
        }
        $menu = admin_read_json('data/menu.json');
        foreach ($menu as $item) {
            if (is_array($item) && ($item['kategori'] ?? '') === $id) {
                throw new DomainException('Kategori masih memiliki menu. Pindahkan atau hapus menu tersebut sebelum menghapus kategori.');
            }
        }
        admin_mutate_json('data/categories.json', function (&$categories) use ($id) {
            foreach ($categories as $index => $category) {
                if (($category['id'] ?? '') === $id) {
                    array_splice($categories, $index, 1);
                    return true;
                }
            }
            throw new OutOfBoundsException('Kategori tidak ditemukan.');
        });
        admin_json_response(['ok' => true]);
    }

    if ($action === 'reorder') {
        $ids = $input['ids'] ?? null;
        if (!is_array($ids) || array_filter($ids, function ($id) { return !is_string($id); }) ||
            count($ids) !== count(array_unique($ids))) {
            category_error('Urutan kategori tidak valid.');
        }
        $group = $input['kelompok'] ?? '';
        if (!in_array($group, ['makanan', 'minuman'], true)) {
            category_error('Kelompok kategori tidak valid.');
        }
        admin_mutate_json('data/categories.json', function (&$categories) use ($ids, $group) {
            $groupIds = [];
            foreach ($categories as $category) {
                if (($category['kelompok'] ?? '') === $group) {
                    $groupIds[] = (string)($category['id'] ?? '');
                }
            }
            $expected = $groupIds;
            sort($expected);
            $provided = $ids;
            sort($provided);
            if ($provided !== $expected) {
                throw new DomainException('Daftar kategori berubah. Muat ulang halaman lalu coba lagi.');
            }
            $byId = [];
            foreach ($categories as $category) {
                $byId[$category['id']] = $category;
            }
            $orderedGroup = array_map(function ($id) use ($byId) { return $byId[$id]; }, $ids);
            $next = [];
            $groupPosition = 0;
            foreach ($categories as $category) {
                if (($category['kelompok'] ?? '') === $group) {
                    $next[] = $orderedGroup[$groupPosition++];
                } else {
                    $next[] = $category;
                }
            }
            $categories = $next;
        });
        admin_json_response(['ok' => true]);
    }

    category_error('Aksi kategori tidak dikenal.');
} catch (DomainException $error) {
    category_error($error->getMessage(), 409);
} catch (OutOfBoundsException $error) {
    category_error($error->getMessage(), 404);
} catch (Throwable $error) {
    error_log('Admin category update failed: ' . $error->getMessage());
    category_error('Kategori gagal diperbarui.', 500);
}
