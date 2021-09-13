<?php

class RussianNameWorker
{
	/**
	 * Name gender
	*/
	private $M = 'm';
	private $F = 'f';

	/**
	 * Name cases
	*/
	private $NOM_CASE = 'nom';
	private $GEN_CASE = 'gen';
	private $DAT_CASE = 'dat';
	private $ACC_CASE = 'acc';
	private $INS_CASE = 'ins';
	private $POS_CASE = 'pre';

	private $rules = [
		'last_name'   => [
			'exceptions' => [
				'	дюма,тома,дега,люка,ферма,гамарра,петипа,шандра . . . . .',
				'	гусь,ремень,камень,онук,богода,нечипас,долгопалец,маненок,рева,кива . . . . .',
				'	вий,сой,цой,хой -я -ю -я -ем -е'
			],
			'suffixes'   => [
				'f	б,в,г,д,ж,з,й,к,л,м,н,п,р,с,т,ф,х,ц,ч,ш,щ,ъ,ь . . . . .',
				'f	ска,цка  -ой -ой -ую -ой -ой',
				'f	ая       --ой --ой --ую --ой --ой',
				'	ская     --ой --ой --ую --ой --ой',
				'f	на       -ой -ой -у -ой -ой',
				
				'	иной -я -ю -я -ем -е',
				'	уй   -я -ю -я -ем -е',
				'	ца   -ы -е -у -ей -е',
					
				'	рих  а у а ом е',
		
				'	ия                      . . . . .',
				'	иа,аа,оа,уа,ыа,еа,юа,эа . . . . .',
				'	их,ых                   . . . . .',
				'	о,е,э,и,ы,у,ю           . . . . .',
		
				'	ова,ева            -ой -ой -у -ой -ой',
				'	га,ка,ха,ча,ща,жа  -и -е -у -ой -е',
				'	ца  -и -е -у -ей -е',
				'	а   -ы -е -у -ой -е',
		
				'	ь   -я -ю -я -ем -е',
		
				'	ия  -и -и -ю -ей -и',
				'	я   -и -е -ю -ей -е',
				'	ей  -я -ю -я -ем -е',
		
				'	ян,ан,йн   а у а ом е',
		
				'	ынец,обец  --ца --цу --ца --цем --це',
				'	онец,овец  --ца --цу --ца --цом --це',
		
				'	ц,ч,ш,щ   а у а ем е',
		
				'	ай  -я -ю -я -ем -е',
				'	гой,кой  -го -му -го --им -м',
				'	ой  -го -му -го --ым -м',
				'	ах,ив   а у а ом е',
		
				'	ший,щий,жий,ний  --его --ему --его -м --ем',
				'	кий,ый   --ого --ому --ого -м --ом',
				'	ий       -я -ю -я -ем -и',
					
				'	ок  --ка --ку --ка --ком --ке',
				'	ец  --ца --цу --ца --цом --це',
					
				'	в,н   а у а ым е',
				'	б,г,д,ж,з,к,л,м,п,р,с,т,ф,х   а у а ом е'
			]
		],
		'first_name'  => [
			'exceptions' => [
				'	лев    --ьва --ьву --ьва --ьвом --ьве',
				'	павел  --ла  --лу  --ла  --лом  --ле',
				'm	шота   . . . . .',
				'm	пётр   ---етра ---етру ---етра ---етром ---етре',
				'f	рашель,нинель,николь,габриэль,даниэль   . . . . .'
			],
			'suffixes'   => [
				'	е,ё,и,о,у,ы,э,ю   . . . . .',
				'f	б,в,г,д,ж,з,й,к,л,м,н,п,р,с,т,ф,х,ц,ч,ш,щ,ъ   . . . . .',

				'f	ь   -и -и . ю -и',
				'm	ь   -я -ю -я -ем -е',

				'	га,ка,ха,ча,ща,жа  -и -е -у -ой -е',
				'	ша  -и -е -у -ей -е',
				'	а   -ы -е -у -ой -е',
				'	ия  -и -и -ю -ей -и',
				'	я   -и -е -ю -ей -е',
				'	ей  -я -ю -я -ем -е',
				'	ий  -я -ю -я -ем -и',
				'	й   -я -ю -я -ем -е',
				'	б,в,г,д,ж,з,к,л,м,н,п,р,с,т,ф,х,ц,ч	 а у а ом е'
			]
		],
		'middle_name' => [
			'suffixes' => [
				'	ич   а  у  а  ем  е',
				'	на  -ы -е -у -ой -е'
			]
		]
	];

	private $initialized = false;

	function init ()
	{
		if ($this->initialized)
			return;

		$this->prepareRules();

		$this->initialized = true;
	}

	function prepareRules ()
	{
		foreach ($this->rules as $type => $value) 
		{
			foreach ($this->rules[$type] as $key => $rule) 
			{
				$length = count($this->rules[$type][$key]);

				for ($i = 0, $n = $length; $i < $n; $i++)
				{
					$this->rules[$type][$key][$i] = $this->rule($this->rules[$type][$key][$i]);
				}
			}
		}
	}

	function rule ($rule)
	{
		$m = [];

		if (preg_match("/^\s*([fm]?)\s*(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s*$/", $rule, $m))
		{
			return [
				'sex'  => $m[1],
				'test' => explode(',', $m[2]),
				'mods' => [$m[3], $m[4], $m[5], $m[6], $m[7]]
			];
		}

		return false;
	}

	function word ($word, $sex, $wordType, $gcase)
	{
		if ($gcase === $this->NOM_CASE) 
			return $word;

		if (preg_match("/[-]/", $word))
		{
			$list = explode('-', $word);
			$length = count($list);

			for ($i = 0, $n = $length; $i < $n; $i++)
			{
				$list[$i] = $this->word($list[i], $sex, $wordType, $gcase);
			}

			return implode('-', $list);
		}

		if (preg_match("/^[А-ЯЁ]\.?$/i)", $word))
			return $word;

		$this->init();

		$rules = $this->rules[$wordType];

		if ($rules['exceptions'])
		{
			$pick = $this->pick($word, $sex, $gcase, $rules['exceptions'], true);

			if ($pick)
				return $pick;
		}

		$pick = $this->pick($word, $sex, $gcase, $rules['suffixes'], false);

		return ($pick !== false ? $pick : $word);
	}

	function pick ($word, $sex, $gcase, $rules, $matchWholeWord)
	{
		$wordLower = strtolower($word);

		$rules_length = count($rules);

		for ($i = 0, $n = $rules_length; $i < $n; $i++)
		{
			if ($this->ruleMatch($wordLower, $sex, $rules[$i], $matchWholeWord))
			{
				return $this->applyMod($word, $gcase, $rules[$i]);
			}
		}

		return false;
	}

	function ruleMatch ($word, $sex, $rule, $matchWholeWord)
	{
		if ($rule['sex'] === $this->M && $sex === $this->F)
			return false;

		if ($rule['sex'] === $this->F && $sex !== $this->F)
			return false;

		$length = count($rule['test']);
		for ($i = 0, $n = $length; $i < $n; $i++)
		{
			$test = $matchWholeWord ? $word : substr($word, max((strlen($word) - strlen($rule['test'][$i])), 0));
			if ($test === $rule['test'][$i])
				return true;
		}

		return false;
	}

	function applyMod ($word, $gcase, $rule)
	{
		$mod = '.';

		switch ($gcase)
		{
			case $this->NOM_CASE:
				$mod = '.';
			break;
			case $this->GEN_CASE:
				$mod = $rule['mods'][0];
			break;
			case $this->DAT_CASE:
				$mod = $rule['mods'][1];
			break;
			case $this->ACC_CASE:
				$mod = $rule['mods'][2];
			break;
			case $this->INS_CASE:
				$mod = $rule['mods'][3];
			break;
			case $this->POS_CASE:
				$mod = $rule['mods'][4];
			break;
			
			default:
				return $word;
		}

		$mod_length = strlen($mod);
		for ($i = 0, $n = $mod_length; $i < $n; $i++)
		{
			$c = substr($mod, $i, 1);

			switch ($c)
			{
				case '.':
				break;
				case '-':
					$word = substr($word, 0, (strlen($word) - 1));
				break;
				default:
					$word .= $c;
				break;
			}
		}

		return $word;
	}
}

class Name
{
	private $is_valid = false;

	private $last_name = '';
	private $first_name = '';
	private $middle_name = '';

	private $gender = NULL;

	private $is_full_name = false;

	function __construct ($last_name = '', $first_name = NULL, $middle_name = NULL, $sex = NULL)
	{
		if ($first_name === NULL)
		{
			$m = [];
			preg_match("/^\s*(\S+)(\s+(\S+)(\s+(\S+))?)?\s*$/", $last_name, $m);

			if ($m)
			{
				$this->is_valid = true;

				if ($m[5] && preg_match("/(ич|на)$/", $m[3]) && !preg_match("/(ич|на)$/", $m[5]))
				{
					$this->last_name = $m[5];
					$this->first_name = $m[1];
					$this->middle_name = $m[3];

					$this->is_full_name = true;
				} else
				{
					$this->last_name = $m[1];
					$this->first_name = $m[3];
					$this->middle_name = $m[5];
				}
			}
		} else {
			$this->last_name = $last_name;
			$this->first_name = $first_name;
			$this->middle_name = $middle_name;

			$this->is_full_name = true;

			$this->is_valid = true;
		}

		$this->gender = $sex ? $sex : $this->getGender();
	}

	function getGender ()
	{
		if ($this->gender)
			return $this->gender;

		if (strlen($this->middle_name) > 2)
		{
			switch (substr($this->middle_name, strlen($this->middle_name) - 2)) 
			{
				case 'ич':
					return 'm';
				break;
				case 'на':
					return 'f';
				break;
				default:
				break;
			}
		}

		return '';
	}

	function getFirstName ()
	{
		return $this->first_name;
	}

	function getLastName ()
	{
		return $this->last_name;
	}

	function getMiddleName ()
	{
		return $this->middle_name;
	}

	function work ($mode, $case)
	{
		$worker = new RussianNameWorker();

		if ($mode === 1) return iconv('UTF-8', 'UTF-8//IGNORE', $worker->word($this->getFirstName(), $this->getGender(), 'first_name', $case));
		if ($mode === 2) return iconv('UTF-8', 'UTF-8//IGNORE', $worker->word($this->getLastName(), $this->getGender(), 'last_name', $case));
		if ($mode === 3) return iconv('UTF-8', 'UTF-8//IGNORE', $worker->word($this->getMiddleName(), $this->getGender(), 'middle_name', $case));

		return '';
	}

	function valid ()
	{
		return $this->is_valid;
	}
}

?>