<?php

namespace CT275\Labs;

use PDO;

class Contact
{
  private ?PDO $db;

  public int $id = -1;
  public string $name = '';
  public string $phone = '';
  public string $notes = '';
  public ?string $avatar = null;
  public string $created_at = '';
  public string $updated_at = '';

  public function __construct(?PDO $pdo)
  {
    $this->db = $pdo;
  }

  public function fill(array $data): self
  {
    $this->name = $data['name'] ?? $this->name;
    $this->phone = $data['phone'] ?? $this->phone;
    $this->notes = $data['notes'] ?? $this->notes;
    $this->avatar = $data['avatar'] ?? $this->avatar;
    return $this;
  }

  public function all(): array
  {
    $contacts = [];
    $statement = $this->db->query('SELECT * FROM contacts ORDER BY id DESC');
    while ($row = $statement->fetch()) {
      $contact = new Contact($this->db);
      $contact->fillFromDB($row);
      $contacts[] = $contact;
    }
    return $contacts;
  }

  public function count(): int
  {
    $statement = $this->db->query('SELECT COUNT(*) FROM contacts');
    return (int) $statement->fetchColumn();
  }

  public function paginate(int $offset, int $limit): array
  {
    $contacts = [];
    $statement = $this->db->prepare('SELECT * FROM contacts ORDER BY id DESC LIMIT :limit OFFSET :offset');
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();

    while ($row = $statement->fetch()) {
      $contact = new Contact($this->db);
      $contact->fillFromDB($row);
      $contacts[] = $contact;
    }
    return $contacts;
  }

  public function find(int $id): ?Contact
  {
    $statement = $this->db->prepare('SELECT * FROM contacts WHERE id = :id');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();

    if ($row) {
      $this->fillFromDB($row);
      return $this;
    }
    return null;
  }

  public function save(): bool
  {
    $result = false;

    if ($this->id >= 0) {
      $statement = $this->db->prepare(
        'UPDATE contacts SET name = :name, phone = :phone, notes = :notes, avatar = :avatar, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
      );
      $result = $statement->execute([
        'name' => $this->name,
        'phone' => $this->phone,
        'notes' => $this->notes,
        'avatar' => $this->avatar,
        'id' => $this->id
      ]);
    } else {
      $statement = $this->db->prepare(
        'INSERT INTO contacts (name, phone, notes, avatar, created_at, updated_at) VALUES (:name, :phone, :notes, :avatar, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
      );
      $result = $statement->execute([
        'name' => $this->name,
        'phone' => $this->phone,
        'notes' => $this->notes,
        'avatar' => $this->avatar
      ]);

      if ($result) {
        $this->id = (int) $this->db->lastInsertId();
      }
    }

    return $result;
  }

  public function update(array $data): bool
  {
    $this->fill($data);
    return $this->save();
  }

  public function delete(): bool
  {
    if (!empty($this->avatar)) {
      $avatarFile = __DIR__ . '/../../public' . $this->avatar;
      if (file_exists($avatarFile)) {
        unlink($avatarFile);
      }
    }

    $statement = $this->db->prepare('DELETE FROM contacts WHERE id = :id');
    return $statement->execute(['id' => $this->id]);
  }

  public function validate(array $data): array
  {
    $errors = [];

    if (empty($data['name'])) {
      $errors['name'] = 'Name is required.';
    }

    if (empty($data['phone'])) {
      $errors['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $data['phone'])) {
      $errors['phone'] = 'Invalid phone number format.';
    }

    if (isset($data['notes']) && strlen($data['notes']) > 255) {
      $errors['notes'] = 'Notes must be less than 255 characters.';
    }

    return $errors;
  }

  private function fillFromDB(array $row): void
  {
    $this->id = (int) $row['id'];
    $this->name = $row['name'] ?? '';
    $this->phone = $row['phone'] ?? '';
    $this->notes = $row['notes'] ?? '';
    $this->avatar = $row['avatar'] ?? null;
    $this->created_at = $row['created_at'] ?? '';
    $this->updated_at = $row['updated_at'] ?? '';
  }
}
