<?php

namespace App\Entity;

use App\Entity\Hours;
use App\Entity\Gender;
use App\Entity\Diet\Diet;
use App\Entity\EnergyGroup;
use App\Entity\Diet\SubDiet;
use Doctrine\DBAL\Types\Types;
use App\Service\ProfileHandler;
use App\Service\UploaderHelper;
use App\Entity\PhysicalActivity;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\NutrientRecommendationUser;
use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *     fields={"email"},
 *     message="Cette adresse email est déja utilisée",
 *     groups={"profile_parameters", "registration"} 
 * )
 * @UniqueEntity(
 *     fields={"username"},
 *     message="Ce pseudonyme est déja utilisé",
 *     groups={"profile_parameters", "registration"}
 * )
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank(
     *      message="Merci de saisir un identifiant",
     *      groups={"profile_parameters", "registration"}
     * )
     * @Assert\Length(
     *      min=3, 
     *      minMessage="Votre nom doit comporter au moins {{ limit }} caractères.",
     *      groups={"profile_parameters", "registration"}
     * )
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank(payload={"severity"="warning"}, message="Merci de saisir une adresse email", groups={"registration"})
     * @Assert\Email(groups={"profile_parameters", "registration"})
     */
    private $email;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="boolean")
     */
    private $firstFillProfile = false;

    /**
     * @ORM\Column(type="array")
     */
    private $validStepProfiles = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Gender", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     * @AppAssert\CanEditProfile(groups={"profile_gender"})
     */
    private $gender;

    /**
     * @ORM\Column(name="height", type="string", nullable=true)
     * @Assert\Range(
     *      min = 100,
     *      max = 260,
     *      notInRangeMessage = "La taille doit être comprise entre {{ min }} cm et {{ max }} cm",
     *      groups={"profile_height"}
     * )
     * @AppAssert\CanEditProfile(groups={"profile_height"})
     */
    private $height;

    /**
     * @ORM\Column(name="weight", type="integer", nullable=true)
     * @Assert\Range(
     *      min = 35,
     *      max = 700,
     *      notInRangeMessage = "Le poids doit être compris entre {{ min }} kg et {{ max }} kg",
     *      groups={"profile_weight"}
     * )
     * @AppAssert\CanEditProfile(groups={"profile_weight"})
     */
    private $weight;

    /**
     * @ORM\Column(name="weight_evolution", type="array", nullable=true)
     */
    private $weightEvolution;

    /**
     * @ORM\Column(name="ideal_weight", type="integer", nullable=true)
     * @Assert\Range(
     *      min = 20,
     *      max = 700,
     *      notInRangeMessage = "Le poids doit être compris entre {{ min }}kg et {{ max }}kg",
     * )
     */
    private $idealWeight;

    /**
     * @ORM\Column(name="imc", type="float", nullable=true)
     */
    private $imc;

    /**
     * @ORM\Column(name="ideal_imc", type="float", nullable=true)
     */
    private $idealImc;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Hour", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     * @Assert\NotBlank(groups={"profile_life"})
     */
    private $hour;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\WorkingType", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     * @AppAssert\CanEditProfile(groups={"profile_workingType"})
     */
    private $workingType;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SportingTime", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     * @AppAssert\CanEditProfile(groups={"profile_sportingTime"})
     */
    private $sportingTime;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $physicalActivity;

    /**
     * @ORM\Column(name="energy", type="float", nullable=true)
     */
    private $energy;

    /**
     * @ORM\Column(name="energy_calculate", type="float", nullable=true)
     */
    private $energyCalculate;
    
    /**
     * @ORM\Column(name="automatic_calculate_energy", type="boolean", nullable=true)
     * @AppAssert\CanEstimateEnergy(groups={"profile_energy"})
     */
    private $automaticCalculateEnergy;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TypeMeal")
     * @ORM\JoinTable(name="user_snacks")
     */
    private $snacks;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Dish")
     * @ORM\JoinTable(name="user_favorite_dish")
     */
    private $favoriteDishes;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=255, nullable=true)
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=255, nullable=true)
     */
    private $phoneNumber;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AgeRange")
     * @AppAssert\CanEditProfile
     */
    private $ageRange;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Meal", mappedBy="user", cascade={"persist"})
     */
    private $meals;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Diet\Diet")
     * @ORM\JoinTable(name="user_diets")
     */
    private $diets;

    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Food")
     * @ORM\JoinTable(name="user_forbidden_foods")
     */
    private $forbiddenFoods;

     /**
     * @var array
     *
     * @ORM\Column(name="recommended_quantities", type="array", nullable="true")
     */
    private $recommendedQuantities;

    /**
     * @var Collection
     * 
     * @ORM\OneToMany(targetEntity="App\Entity\NutrientRecommendationUser", mappedBy="user", cascade={"persist"})
     */
    private Collection $nutrientRecommendations;

    /**
    * @var string
    *
    * @ORM\Column(name="picture", type="string", length=255, nullable=true)
    */
    private $picture;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $country;

     /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $sodium;

     /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $carboHydrate;

     /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lipid;

     /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $protein;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $registerAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $googleId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $githubId;

    public function __construct()
    {
        $this->meals = new ArrayCollection();
        $this->forbiddenFoods = new ArrayCollection();
        $this->snacks = new ArrayCollection();
        $this->favoriteDishes = new ArrayCollection();
        $this->diets = new ArrayCollection();
        $this->nutrientRecommendations = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->username;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials()
    {
        
    }

    public function getHeight(): ?string
    {
        return $this->height;
    }

    public function setHeight(?string $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getImc(): ?float
    {
        return $this->imc;
    }

    public function setImc(?float $imc): self
    {
        $this->imc = $imc;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getHour(): ?Hour
    {
        return $this->hour;
    }

    public function setHour(?Hour $hour): self
    {
        $this->hour = $hour;

        return $this;
    }

    /**
     * Set imc
     *
     */
    public function setValueIdealWeight()
    {
        switch ($this->getGender()->getAlias()) 
        {
            case Gender::MALE:
                $this->idealWeight = round($this->height - 100 - (($this->height - 150) / 4));
                break;
            case Gender::FEMALE:
                $this->idealWeight = round($this->height - 100 - (($this->height - 150) / 2.5));
                break;
            default:
                $this->idealWeight = round($this->height - 100 - (($this->height - 150) / 4));
                break;
        }
    }

    /**
     * Set imc
     *
     */
    public function setValueImc()
    {
        if (null !== $this->height)
        {
            $heightMeter = $this->height / 100;
            $this->imc = $this->weight / ($heightMeter * $heightMeter);
        }else{
            $this->imc = null;
        }

        return $this;
    }

    /**
     * Set imc
     *
     */
    public function setValueIdealImc()
    {
        if (null !== $this->height)
        {
            $heightMeter = $this->height / 100;
            $this->idealImc = $this->idealWeight / ($heightMeter * $heightMeter);
        }else{
            $this->idealImc = null;
        }

        return $this;
    }

    // public function isAnHardWorker()
    // {
    //     return ('H_NORMAL' != $this->hour->getCode() || $this->physicalActivity->getForHardWorker());
    // }

    /**
     * @return Collection|Meal[]
     */
    public function getMeals(): Collection
    {
        return $this->meals;
    }

    public function addMeal(Meal $meal): self
    {
        if (!$this->meals->contains($meal)) {
            $this->meals[] = $meal;
            $meal->setUser($this);
        }

        return $this;
    }

    public function removeMeal(Meal $meal): self
    {
        if ($this->meals->contains($meal)) {
            $this->meals->removeElement($meal);
            // set the owning side to null (unless already changed)
            if ($meal->getUser() === $this) {
                $meal->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TypeMeal[]
     */
    public function getSnacks(): Collection
    {
        return $this->snacks;
    }

    public function addSnack(TypeMeal $snack): self
    {
        if (!$this->snacks->contains($snack)) {
            $this->snacks[] = $snack;
        }

        return $this;
    }

    public function removeSnack(TypeMeal $snack): self
    {
        if ($this->snacks->contains($snack)) {
            $this->snacks->removeElement($snack);
        }

        return $this;
    }

    /**
     * @return Collection|TypeMeal[]
     */
    public function getFavoriteDishes(): Collection
    {
        return $this->favoriteDishes;
    }

    public function addFavoriteDishes(Dish $dish): self
    {
        if (!$this->favoriteDishes->contains($dish)) {
            $this->favoriteDishes[] = $dish;
        }

        return $this;
    }

    public function removeFavoriteDishes(Dish $dish): self
    {
        if ($this->favoriteDishes->contains($dish)) {
            $this->favoriteDishes->removeElement($dish);
        }

        return $this;
    }

    /**
     * @return Collection|Diet[]
     */
    public function getDiets(): Collection
    {
        return $this->diets;
    }

    public function addDiet(Diet $diet): self
    {
        if (!$this->diets->contains($diet)) {
            $this->diets[] = $diet;
        }

        return $this;
    }

    public function removeDiet(Diet $diet): self
    {
        if ($this->diets->contains($diet)) {
            $this->diets->removeElement($diet);
        }

        return $this;
    }

    public function getEnergy(): ?float
    {
        return $this->energy;
    }

    public function setEnergy(?float $energy): self
    {
        $this->energy = $energy;

        return $this;
    }

    public function getEnergyKj(): ?float
    {
        return $this->getEnergy() / 0.2388;
    }

    public function getRecommendedQuantities(): ?array
    {
        return $this->recommendedQuantities;
    }

    public function setRecommendedQuantities(array $recommendedQuantities): self
    {
        $this->recommendedQuantities = $recommendedQuantities;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    // public function getHasCompleteProfil(): ?bool
    // {
    //     if($this->gender && $this->birthday && $this->height && $this->weight && $this->physicalActivity)
    //         return true;

    //     return false;
    // }

    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }
    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    /**
     * @return Collection|Food[]
     */
    public function getForbiddenFoods(): Collection
    {
        return $this->forbiddenFoods;
    }

    public function addForbiddenFood(Food $forbiddenFood): self
    {
        if (!$this->forbiddenFoods->contains($forbiddenFood)) {
            $this->forbiddenFoods[] = $forbiddenFood;
        }

        return $this;
    }

    public function removeForbiddenFood(Food $forbiddenFood): self
    {
        $this->forbiddenFoods->removeElement($forbiddenFood);

        return $this;
    }

    public function getPicturePath(): ?string
    {
        if($this->getPicture()) {
            return UploaderHelper::USER.'/'.$this->getPicture();
        }

        return null;
    }

    public function getAutomaticCalculateEnergy(): ?bool
    {
        return $this->automaticCalculateEnergy;
    }

    public function setAutomaticCalculateEnergy(bool $automaticCalculateEnergy): self
    {
        $this->automaticCalculateEnergy = $automaticCalculateEnergy;

        return $this;
    }

    

    public function getValidStepProfiles(): ?array
    {
        return $this->validStepProfiles;
    }

    public function setValidStepProfiles(array $validStepProfiles): self
    {
        $this->validStepProfiles = $validStepProfiles;

        return $this;
    }

    public function addValidStepProfiles(string $step): self
    {
        $this->validStepProfiles[] = $step;

        return $this;
    }

    // public function isProfileCompleted()
    // {
    //     return count($this->validStepProfiles) == count(ProfileHandler::$steps);
    // }

    



    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function hasFirstFillProfile(): ?bool
    {
        return $this->firstFillProfile;
    }

    public function setFirstFillProfile(bool $firstFillProfile): self
    {
        $this->firstFillProfile = $firstFillProfile;

        return $this;
    }

    public function getFirstFillProfile(): ?bool
    {
        return $this->firstFillProfile;
    }

    public function getAgeRange(): ?AgeRange
    {
        return $this->ageRange;
    }

    public function setAgeRange(?AgeRange $ageRange): self
    {
        $this->ageRange = $ageRange;

        return $this;
    }

    public function getPhysicalActivity(): ?float
    {
        return $this->physicalActivity;
    }

    public function setPhysicalActivity(float $physicalActivity): self
    {
        $this->physicalActivity = $physicalActivity;

        return $this;
    }

    public function getWorkingType(): ?WorkingType
    {
        return $this->workingType;
    }

    public function setWorkingType(?WorkingType $workingType): self
    {
        $this->workingType = $workingType;

        return $this;
    }

    public function getSportingTime(): ?SportingTime
    {
        return $this->sportingTime;
    }

    public function setSportingTime(?SportingTime $sportingTime): self
    {
        $this->sportingTime = $sportingTime;

        return $this;
    }

    public function isFirstFillProfile(): ?bool
    {
        return $this->firstFillProfile;
    }

    public function isIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function isAutomaticCalculateEnergy(): ?bool
    {
        return $this->automaticCalculateEnergy;
    }

    /**
     * @return Collection<int, NutrientRecommendationsUser>
     */
    public function getNutrientRecommendations(): Collection
    {
        return $this->nutrientRecommendations;
    }

    public function addNutrientRecommendation(NutrientRecommendationUser $nutrientRecommendation): static
    {
        if (!$this->nutrientRecommendations->contains($nutrientRecommendation)) {
            $this->nutrientRecommendations->add($nutrientRecommendation);
            $nutrientRecommendation->setUser($this);
        }

        return $this;
    }

    public function removeNutrientRecommendation(NutrientRecommendationUser $nutrientRecommendation): static
    {
        if ($this->nutrientRecommendations->removeElement($nutrientRecommendation)) {
            // set the owning side to null (unless already changed)
            if ($nutrientRecommendation->getUser() === $this) {
                $nutrientRecommendation->setUser(null);
            }
        }

        return $this;
    }

    public function getSodium(): ?float
    {
        return $this->sodium;
    }

    public function setSodium(?float $sodium): static
    {
        $this->sodium = $sodium;

        return $this;
    }

    public function getCarboHydrate(): ?float
    {
        return $this->carboHydrate;
    }

    public function setCarboHydrate(?float $carboHydrate): static
    {
        $this->carboHydrate = $carboHydrate;

        return $this;
    }

    public function getLipid(): ?float
    {
        return $this->lipid;
    }

    public function setLipid(?float $lipid): static
    {
        $this->lipid = $lipid;

        return $this;
    }

    public function getProtein(): ?float
    {
        return $this->protein;
    }

    public function setProtein(?float $protein): static
    {
        $this->protein = $protein;

        return $this;
    }

    public function getIdealWeight(): ?int
    {
        return $this->idealWeight;
    }

    public function setIdealWeight(?int $idealWeight): static
    {
        $this->idealWeight = $idealWeight;

        return $this;
    }

    public function getIdealImc(): ?float
    {
        return $this->idealImc;
    }

    public function setIdealImc(?float $idealImc): static
    {
        $this->idealImc = $idealImc;

        return $this;
    }

    public function getEnergyCalculate(): ?float
    {
        return $this->energyCalculate;
    }

    public function setEnergyCalculate(?float $energyCalculate): static
    {
        $this->energyCalculate = $energyCalculate;

        return $this;
    }

    public function getWeightEvolution(): ?array
    {
        return $this->weightEvolution;
    }

    public function setWeightEvolution(?array $weightEvolution): static
    {
        $this->weightEvolution = $weightEvolution;

        return $this;
    }

    /**
     * Set register date
     *
     * @ORM\PrePersist
     */
    public function setRegisterAtValue()
    {
        $dateDay = new \DateTime();

        $this->registerAt = $dateDay->format('m/d/Y');
    }

    public function getRegisterAt(): ?string
    {
        return $this->registerAt;
    }

    public function setRegisterAt(?string $registerAt): static
    {
        $this->registerAt = $registerAt;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getGithubId(): ?string
    {
        return $this->githubId;
    }

    public function setGithubId(?string $githubId): self
    {
        $this->githubId = $githubId;
        return $this;
    }
}
