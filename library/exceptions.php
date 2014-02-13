<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi {
    /**
     * Base class for Emyi Exceptions.
     */
    class Exception extends \Exception
    {
    }
}

//----------------------------------------------------------------------------
namespace Emyi\Mvc {
    use Emyi;

    /**
     * Represents an error raised by Emyi\Mvc
     */
    class Exception extends Emyi\Exception
    {
    }
}

//----------------------------------------------------------------------------
namespace Emyi\Auth {
    use Emyi;

    /**
     * Represents an error raised by Emyi\Auth
     */
    class Exception extends Emyi\Exception
    {
    }
}

//----------------------------------------------------------------------------
namespace Emyi\Util {
    use Emyi;

    /**
     * Represents an error raised by Emyi\Util
     */
    class ConfigException extends Emyi\Exception
    {
    }
}

//----------------------------------------------------------------------------
namespace Emyi\Http {
    use Emyi;

    /*
     * Represents an error raised by Emyi\Http
     */
    class Exception extends Emyi\Exception
    {
    }

    // in use?
    class RequestException extends Exception
    {
    }

    // in use?
    class ResponseException extends Exception
    {
    }
}
