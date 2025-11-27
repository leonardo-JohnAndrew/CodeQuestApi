package com.codechecker.JavaAnalyzer; 
import java.io.File;
import java.util.HashMap;
import java.util.Map;
import com.github.javaparser.StaticJavaParser;
import com.github.javaparser.ast.CompilationUnit;
import com.github.javaparser.ast.body.ClassOrInterfaceDeclaration;
import com.github.javaparser.ast.body.MethodDeclaration;
import com.github.javaparser.ast.body.VariableDeclarator;
import com.github.javaparser.ast.expr.ArrayCreationExpr;
import com.github.javaparser.ast.expr.MethodCallExpr;
import com.github.javaparser.ast.stmt.ForStmt;
import com.github.javaparser.ast.stmt.DoStmt;
import com.github.javaparser.ast.stmt.WhileStmt;
import com.github.javaparser.ast.stmt.IfStmt;
import com.github.javaparser.ast.stmt.SwitchStmt;
import com.github.javaparser.ast.expr.ArrayInitializerExpr;
import com.github.javaparser.ast.expr.ObjectCreationExpr;
import com.github.javaparser.ast.expr.ArrayCreationExpr;

public class CodeAnalyzer {

    public static void main(String[] args) {
        if(args.length < 1){
            System.out.println("Usage: java CodeAnalyzer <JavaFile>");
            return;
        }

        File file = new File(args[0]);
        Map<String, Boolean> report = new HashMap<>();
        try {
            CompilationUnit cu = StaticJavaParser.parse(file);

            // 1. OOP: check for classes
            report.put("OOP", cu.findAll(ClassOrInterfaceDeclaration.class).size() > 0);

            // 2. Inheritance
            boolean hasInheritance = cu.findAll(ClassOrInterfaceDeclaration.class).stream()
                    .anyMatch(c -> c.getExtendedTypes().size() > 0);
            report.put("Inheritance", hasInheritance);

            // 3. Interfaces
            boolean hasInterface = cu.findAll(ClassOrInterfaceDeclaration.class).stream()
                    .anyMatch(c -> c.isInterface());
            report.put("Interface", hasInterface);

            // 4. Loops
            boolean hasLoops = !cu.findAll(ForStmt.class).isEmpty() ||
                               !cu.findAll(WhileStmt.class).isEmpty() ||
                               !cu.findAll(DoStmt.class).isEmpty();
            report.put("Loops", hasLoops);

            // 5. Conditionals
            boolean hasIfElse = !cu.findAll(IfStmt.class).isEmpty();
            report.put("Conditionals", hasIfElse);

            // 6. Switch/Case
            boolean hasSwitch = !cu.findAll(SwitchStmt.class).isEmpty();
            report.put("Switch", hasSwitch);

   // Arrays
boolean hasArray = cu.findAll(VariableDeclarator.class).stream()
    .anyMatch(v -> 
        v.getType().isArrayType() || 
        (v.getInitializer().isPresent() && 
         (v.getInitializer().get() instanceof ArrayInitializerExpr ||
          v.getInitializer().get() instanceof ArrayCreationExpr))
    );
report.put("Arrays", hasArray);

// Collections
boolean hasCollections = cu.findAll(VariableDeclarator.class).stream()
    .anyMatch(v -> {
        if (v.getType().isClassOrInterfaceType()) {
            String simpleName = v.getType().asClassOrInterfaceType().getName().asString();
            if (simpleName.equals("List") || simpleName.equals("Map") || simpleName.equals("Set")) return true;
        }
        if (v.getInitializer().isPresent() && v.getInitializer().get() instanceof ObjectCreationExpr) {
            ObjectCreationExpr oce = (ObjectCreationExpr) v.getInitializer().get();
            String className = oce.getType().getName().asString();
            if (className.equals("ArrayList") || className.equals("HashMap") || className.equals("HashSet")) return true;
        }
        return false;
    });
report.put("Collections", hasCollections);




            // 9. Recursion
            boolean hasRecursion = cu.findAll(MethodDeclaration.class).stream()
                    .anyMatch(m -> m.getBody().isPresent() &&
                                   m.getBody().get().toString().contains(m.getNameAsString() + "("));
            report.put("Recursion", hasRecursion);

            // 10. Print statements
            boolean hasPrint = cu.findAll(MethodCallExpr.class).stream()
                    .anyMatch(mc -> mc.getNameAsString().matches("print|println"));
            report.put("Print", hasPrint);

            // 11. Comments
            boolean hasComments = !cu.getAllContainedComments().isEmpty();
            report.put("Comments", hasComments);

        } catch (Exception e) {
            System.out.println("Error parsing file: " + e.getMessage());
        }

        // Print report as JSON-like string
        System.out.println(report);
    }
}
