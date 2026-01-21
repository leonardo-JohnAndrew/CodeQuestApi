package com.codechecker.JavaAnalyzer;

import java.io.File;
import java.util.*;

import com.github.javaparser.*;
import com.github.javaparser.ast.*;
import com.github.javaparser.ast.body.*;
import com.github.javaparser.ast.expr.*;
import com.github.javaparser.ast.stmt.*;

public class CodeAnalyzer {

    public static void main(String[] args) {

        // ---------- SAFETY CHECK ----------
        if (args.length < 1) {
            System.out.println("{\"syntaxError\":true,\"message\":\"No file provided\"}");
            return;
        }

        try {
            File file = new File(args[0]);
            CompilationUnit cu = StaticJavaParser.parse(file);

            // =================================================
            // FEATURES
            // =================================================
            Map<String, Boolean> features = new HashMap<>();

            // ---------- OOP ----------
            boolean hasClass = !cu.findAll(ClassOrInterfaceDeclaration.class).isEmpty();
            boolean hasFields = !cu.findAll(FieldDeclaration.class).isEmpty();
            boolean hasMethods = cu.findAll(MethodDeclaration.class)
                                   .stream().anyMatch(m -> !m.getNameAsString().equals("main"));
            boolean hasObjectCreation = !cu.findAll(ObjectCreationExpr.class).isEmpty();

            boolean isOOP = hasClass && (hasFields || hasMethods || hasObjectCreation);
            features.put("OOP", isOOP);

            // ---------- Loops ----------
            features.put("Loops",
                !cu.findAll(ForStmt.class).isEmpty() ||
                !cu.findAll(WhileStmt.class).isEmpty() ||
                !cu.findAll(DoStmt.class).isEmpty()
            );

            // ---------- Conditionals ----------
            features.put("Conditionals", !cu.findAll(IfStmt.class).isEmpty());

            // ---------- Inheritance ----------
            features.put("Inheritance",
                cu.findAll(ClassOrInterfaceDeclaration.class)
                  .stream().anyMatch(c -> !c.getExtendedTypes().isEmpty())
            );

            // ---------- Switch ----------
            features.put("Switch", !cu.findAll(SwitchStmt.class).isEmpty());

            // ---------- Arrays ----------
            boolean hasArrays =
                cu.findAll(VariableDeclarator.class).stream().anyMatch(v ->
                    v.getType().isArrayType() ||
                    (v.getInitializer().isPresent() &&
                     (v.getInitializer().get() instanceof ArrayInitializerExpr ||
                      v.getInitializer().get() instanceof ArrayCreationExpr))
                );
            features.put("Arrays", hasArrays);

            // ---------- Collections ----------
            boolean hasCollections =
                cu.findAll(ObjectCreationExpr.class).stream().anyMatch(o -> {
                    String name = o.getType().getNameAsString();
                    return name.equals("ArrayList") ||
                           name.equals("HashMap") ||
                           name.equals("HashSet") ||
                           name.equals("LinkedList");
                });
            features.put("Collections", hasCollections);

            // ---------- Recursion ----------
            boolean hasRecursion =
                cu.findAll(MethodDeclaration.class).stream().anyMatch(m ->
                    m.getBody().isPresent() &&
                    m.getBody().get().toString().contains(m.getNameAsString() + "(")
                );
            features.put("Recursion", hasRecursion);

            // ---------- Print ----------
            boolean hasPrint =
                cu.findAll(MethodCallExpr.class).stream()
                  .anyMatch(m -> m.getNameAsString().matches("print|println"));
            features.put("Print", hasPrint);

            // ---------- Comments ----------
            features.put("Comments", !cu.getAllContainedComments().isEmpty());

            // =================================================
            // METRICS
            // =================================================
            int cyclomatic =
                1 +
                cu.findAll(IfStmt.class).size() +
                cu.findAll(ForStmt.class).size() +
                cu.findAll(WhileStmt.class).size() +
                cu.findAll(DoStmt.class).size() +
                cu.findAll(SwitchStmt.class).size();

            int statements = cu.findAll(Statement.class).size();
            int nodes = cu.getChildNodes().size();

            // =================================================
            // JSON OUTPUT (FINAL)
            // =================================================
         String featuresJson = "{";
for (Map.Entry<String, Boolean> e : features.entrySet()) {
    featuresJson += "\"" + e.getKey() + "\":" + e.getValue() + ",";
}
if (featuresJson.endsWith(",")) {
    featuresJson = featuresJson.substring(0, featuresJson.length() - 1);
}
featuresJson += "}";

String json =
    "{"
    + "\"syntaxError\":false,"
    + "\"features\":" + featuresJson + ","
    + "\"metrics\":{"
        + "\"cyclomatic\":" + cyclomatic + ","
        + "\"statements\":" + statements + ","
        + "\"nodes\":" + nodes
    + "}"
    + "}";

System.out.println(json);


        } catch (Exception e) {
            System.out.println(
                "{\"syntaxError\":true,\"message\":\"" +
                e.getMessage().replace("\"", "'") + "\"}"
            );
        }
    }

    // =================================================
    // HELPER: Map -> JSON
    // =================================================
    private static String mapToJson(Map<String, Boolean> map) {
        StringBuilder sb = new StringBuilder("{");
        for (Map.Entry<String, Boolean> e : map.entrySet()) {
            sb.append("\"").append(e.getKey()).append("\":")
              .append(e.getValue()).append(",");
        }
        if (sb.charAt(sb.length() - 1) == ',') sb.deleteCharAt(sb.length() - 1);
        sb.append("}");
        return sb.toString();
    }
}
