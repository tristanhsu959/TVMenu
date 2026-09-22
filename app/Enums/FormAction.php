<?php

namespace App\Enums;

enum FormAction : int
{
	case LIST 	= 1;
	case CREATE	= 2;
	case UPDATE = 3;
	case DELETE = 4;
}
