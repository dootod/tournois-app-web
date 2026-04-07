package com.example.mobile.model;

import java.util.List;

public class LoginResponse {
    public String token;
    public User user;

    public static class User {
        public int id;
        public String email;
        public List<String> roles;
        public Integer adherent_id;
    }
}
