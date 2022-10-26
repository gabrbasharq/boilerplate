<?php

namespace Application\Error;


class Error
{
    const REQUIRED = "Required argument!";
    const HAS_ASSOCIATION_FIELDS = "Can Not Delete This Item Because It linked with other items!";
    const NOT_FOUND = "Not Found!";
    const EXISTS = "Already Exists!";
}
