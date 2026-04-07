package com.example.mobile;

import com.example.mobile.model.LoginRequest;

import org.junit.Test;

import static org.junit.Assert.assertEquals;

public class LoginRequestTest {
    @Test
    public void loginRequest_storesCredentials() {
        LoginRequest r = new LoginRequest("a@b.c", "pwd");
        assertEquals("a@b.c", r.email);
        assertEquals("pwd", r.password);
    }
}
