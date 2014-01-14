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
namespace Emyi\Db {
    use Emyi;

    /**
     * Represents an error raised by Emyi\Db
     */
    class Exception extends Emyi\Exception
    {
        /**
         * @return Emyi\Db\Exception
         */
        protected static final function unsupportedMethod($method)
        {
            return new self("'$method' is not supported by the driver");
        }
    }

    /**
     * Represents an error raised by Emyi\Db\Connection.
     */
    class ConnectionException extends Exception
    {
    }
}

namespace Emyi\Db\Driver {
    use Emyi\Db;

    /**
     * Represents errors raised at Emyi\Db\Driver namespace.
     */
    class Exception extends Db\Exception
    {
    }

    /**
     * Represents an error raised by Emyi\Db\Driver\Descriptor and child
     * classes.
     */
    class DescriptorException extends Exception
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
