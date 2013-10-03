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
     *
     */
    class Exception extends Emyi\Exception
    {
    }
}

//----------------------------------------------------------------------------
namespace Emyi\Auth {
    use Emyi;

    /**
     * Thrown for configuration problems.
     */
    class Exception extends Emyi\Exception
    {
    }
}

//----------------------------------------------------------------------------
namespace Emyi\Util {
    use Emyi;

    /**
     * Thrown for configuration issues.
     */
    class ConfigException extends Emyi\Exception
    {
    }
}

//----------------------------------------------------------------------------
namespace Emyi\Db {
    use Emyi;

    /**
     * Generic base exception for Emyi\Db specific errors.
     */
    class Exception extends Emyi\Exception
    {
    }

    /**
     * Thrown when a record cannot be found.
     */
    class RecordNotFound extends Exception
    {
    }

    /**
     * Thrown by Model
     */
    class ModelException extends Exception
    {
    }
}

namespace Emyi\Db\Association {
    use Emyi\Db;

    /**
     *
     */
    class Exception extends Db\Exception
    {
    }
}

//----------------------------------------------------------------------------
namespace Emyi\Http {
    use Emyi;

    // in use?
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
