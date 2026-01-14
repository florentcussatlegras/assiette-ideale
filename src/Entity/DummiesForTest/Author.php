<?php

namespace App\Entity\DummiesForTest;

use App\Repository\AuthorRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

/**
 * @ORM\Entity(repositoryClass=AuthorRepository::class)
 */
class Author
{
    const GENRES = ['fiction', 'non-fiction'];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Sequentially(
     *      constraints={@Assert\NotNull(), @Assert\Regex(pattern="/^[a-z]*$/", message="L'email ne doit contenir que des lettres minuscules."), @Assert\Type("string")},
     *      groups={"Group1"}
     * )
     */
    private $emailAdress;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"Group2"})
     */
    private $firstName;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\DummiesForTest\Address")
     * @Assert\Valid()
     */
    private $address;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @Assert\Type(type="integer")
     */
    private $age;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\LessThanOrEqual("-3 weeks", message="{{ value }} must be less or equal to {{ compared_value }} ({{ compared_value_type }})")
     */
    private $birthday;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Type("date")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Timezone(zone=\DateTimeZone::AFRICA, message="Le fuseau doit appartenir à l'Europe")
     */
    private $accessCode;

    // /**
    //  * @ORM\Column(type="array")
    //  * @Assert\All(
    //  *      constraints={
    //  *          @Assert\NotBlank(message="Merci de remplir cette valeur")
    //  *      },
    //  * )
    //  * @Assert\Count(
    //  *      min = 2,
    //  *      max = 2,
    //  *      exactMessage = "Ce tableau doit comporter {{ limit }} éléments"
    //  * )
    //  * @Assert\Collection(
    //  *      fields = {
    //  *          "name" = { @Assert\Length(min=3) },
    //  *          "code" = { @Assert\Regex(pattern="/^#[a-z0-9]{6}/", message="Le code couleur est invalide!") }
    //  *      }
    //  * )
    //  */
    private $favoriteColors = [];

    /**
     * @ORM\Column(type="array")
     * @Assert\Unique(message="{{ label }} ne doit pas comporter de doublons.")
     * @Assert\All(
     *      constraints = {
     *          @Assert\Choice({"New York", "Berlin", "Tokyo"}, message="Veuillez saisir un choix parmi {{ choices }}"),
     *      }
     * )
     */
    private $favoriteTowns = [];

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Choice(choices=Author::GENRES, message="{{ value }} ne fait pas parti des valeurs valides ({{ choices }})")
     */
    private $genre;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Language(message="{{ value }} n'est pas un {{ label }} valide.")
     * @Assert\Choice({"fr", "en", "es", "nl"}, message="Le language {{ value }} n'est pas dans la liste {{ choices }}")
     */
    private $language;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Country(alpha3=true, message="{{ value }} n'est pas un pays valide")
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Locale(canonicalize = true)
     */
    private $locale;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->firstName;
    }

    public function getEmailAdress(): ?string
    {
        return $this->emailAdress;
    }

    public function setEmailAdress(string $emailAdress): self
    {
        $this->emailAdress = $emailAdress;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): self
    {
        $this->age = $age;

        return $this;
    }

    public function getAccessCode(): ?string
    {
        return $this->accessCode;
    }

    public function setAccessCode(string $accessCode): self
    {
        $this->accessCode = $accessCode;

        return $this;
    }

    public function getFavoriteColors(): ?array
    {
        return $this->favoriteColors;
    }

    public function setFavoriteColors(array $favoriteColors): self
    {
        $this->favoriteColors = $favoriteColors;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getFavoriteTowns(): ?array
    {
        return [];
        // return $this->favoriteTowns;
    }

    public function setFavoriteTowns(array $favoriteTowns): self
    {
        $this->favoriteTowns = $favoriteTowns;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(string $genre): self
    {
        $this->genre = $genre;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getGroupSequence(): array
    {
        return ['Group2', 'Group1'];
    }
}
