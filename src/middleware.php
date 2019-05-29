<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

$app->add(new \Tuupola\Middleware\JwtAuthentication([
//    "path" => ["/api", "/admin"]
    "path" => ["/api"],
    "attribute" => "decoded_token_data",
    "secret" => "topsecretagent",
    "algorithm" => ["HS256"],
    "secure" => false,
    "error" => function($response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response->withHeader("Content-Type", "application/json")
                        ->write(json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
    }
]));
