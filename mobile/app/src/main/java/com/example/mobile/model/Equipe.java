package com.example.mobile.model;

public class Equipe {
    public int id;
    public String nom;

    @Override
    public String toString() {
        return nom != null ? nom : ("Équipe #" + id);
    }
}
