<?php
$con = new mysqli('localhost','root','','websitedb');

// Update loan payment to loan repayment
$con->query("UPDATE mono_acc_transaction SET type_of_transaction = 'Loan Repayment' WHERE type_of_transaction = 'Loan Payment'");

// Update loan approval to loan deposit
$con->query("UPDATE mono_acc_transaction SET type_of_transaction = 'Loan Deposit' WHERE type_of_transaction = 'Loan Approval'");

echo "Transaction types updated successfully!";
?> 