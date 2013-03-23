<?php

namespace ExtRes\Exceptions;

/**
 * Краткое описание
 *
 * Полное описание
 *
 * @version   1.0
 * @copyright
 * @see       http://tools.ietf.org/html/rfc6749#section-4.1.2.1
 * @author    Oleg Suvorkov <ovsu@rdw.ru>
 * @issue     #4300
 */
class Connect extends Exception implements \Serializable
{
    /**
     * Не известная ошибка
     */
    const UNKNOWN                   = 0;

    /**
     * В запросе отсутствуют обязательные параметры,
     * присутсвтуют неподдерживаемые параметры или значения,
     * или запрос инным образом не правельный
     */
    const INVALID_REQUEST           = 1;

    /**
     * Идентификатор клиента является не действительным
     */
    const INVALID_CLIENT            = 2;

    /**
     * Клиент не имеет прав использовать запрашиваемый тип ответа
     */
    const UNAUTHORIZED_CLIENT       = 3;

    /**
     * redirect_uri не соответствует заданному при регистрации
     */
    const REDIRECT_URI_MISMATCH     = 4;

    /**
     * Конечному пользователю или серверу отказано в просьбе авторизации
     */
    const ACCESS_DENIED             = 5;

    /**
     * Запрашиваемый тип ответа не поддерживается сервером авторизации.
     */
    const UNSUPPORTED_RESPONSE_TYPE = 6;

    /**
     * Запрашиваемый scope является недействительным, неизвестноым, или неправильным.
     */
    const INVALID_SCOPE             = 7;

    /**
     * При условии что запрашиваемые данные
     * (например, код авторизации, полномочия владельца ресурса) были переданы,
     * они является недействительным, так как истек срок, аннулированы или
     * URL не соответствуют перенаправлению URI, используемые в запрос на авторизацию,
     * или был выдан другому клиенту.
     */
    const INVALID_GRANT             = 8;

    private static $error = array(
        self::INVALID_REQUEST           => 'invalid_request',
        self::INVALID_CLIENT            => 'invalid_client',
        self::UNAUTHORIZED_CLIENT       => 'unauthorized_client',
        self::REDIRECT_URI_MISMATCH     => 'redirect_uri_mismatch',
        self::ACCESS_DENIED             => 'access_denied',
        self::UNSUPPORTED_RESPONSE_TYPE => 'unsupported_response_type',
        self::INVALID_SCOPE             => 'invalid_scope',
        self::INVALID_GRANT             => 'invalid_grant',
    );

    /**
     * @param string|integer    $code
     * @param string|null       $description
     * @param string|null       $uri
     */
    public function __construct($code, $description = null, $uri = null)
    {
        if (
            is_numeric($code)
            && isset(self::$error[$code])
        ) {
            $code = self::$error[$code];
        } elseif (is_string($code)) {
            $code = array_search($code, self::$error, true);
        } elseif (is_integer($code) === false) {
            $code = self::UNKNOWN;
        }
        $this->code = $code;
        if ($description) {
            $this->message = $description;
            if ($uri) {
                $this->message.= ' ('.$uri.')';
            }
        }
    }

    public function serialize()
    {
        return serialize(array(
            $this->code,
            $this->message,
        ));
    }

    public function unserialize( $serialized )
    {
        // TODO: Implement unserialize() method.
    }
}
